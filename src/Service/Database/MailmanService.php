<?php

declare(strict_types=1);

namespace App\Service\Database;

use App\Entity\Application\Enums\ConfigNamespaces;
use App\Entity\Database\AuditMailingListMembership;
use App\Entity\Database\Enums\MailingListMemberAction;
use App\Entity\Database\Enums\MailingListMemberOrigin;
use App\Entity\Database\MailingList;
use App\Entity\Database\MailingListMember;
use App\Entity\Database\MailmanMailingList;
use App\Repository\Database\MailingListMemberRepository;
use App\Repository\Database\MailingListRepository;
use App\Repository\Database\MailmanMailingListRepository;
use App\Repository\Database\MemberRepository;
use App\Service\Application\Config;
use DateInterval;
use DateTime;
use LogicException;
use RuntimeException;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function array_flip;
use function array_map;
use function in_array;
use function json_decode;
use function json_last_error_msg;
use function json_validate;
use function rawurlencode;
use function sprintf;

class MailmanService
{
    private const string MM_ROLE_NONMEMBER = 'nonmember';
    private const string MM_ROLE_MEMBER = 'member';
    private const string MM_ROLE_MODERATOR = 'moderator';
    private const string MM_ROLE_OWNER = 'owner';

    private const string MM_DELIVERYMODE_REGULAR = 'regular';
    private const string MM_DELIVERYMODE_DIGESTS_MIME = 'mime_digests';
    private const string MM_DELIVERYMODE_DIGESTS_PLAIN = 'plaintext_digests';
    private const string MM_DELLIVERYMODE_DIGESTS_SUMMARY = 'summary_digests';

    private const string MM_DELIVERYSTATUS_ENABLED = 'enabled';
    private const string MM_DELIVERYSTATUS_DISABLED_BY_USER = 'by_user';
    private const string MM_DELIVERYSTATUS_DISABLED_BY_BOUNCES = 'by_bounces';
    private const string MM_DELIVERYSTATUS_DISABLED_BY_MODERATOR = 'by_moderator';

    public function __construct(
        private readonly MailingListRepository $mailingListRepository,
        private readonly MailmanMailingListRepository $mailmanMailingListRepository,
        private readonly MailingListMemberRepository $mailingListMemberRepository,
        private readonly MemberRepository $memberRepository,
        private readonly Config $configService,
        private readonly Audit $auditService,
        private readonly HttpClientInterface $httpClient,
        #[Autowire(env: 'MAILMAN_API_ENDPOINT')]
        private readonly string $endpoint,
        #[Autowire(env: 'MAILMAN_API_VERSION')]
        private readonly string $version,
        #[Autowire(env: 'MAILMAN_API_USERNAME')]
        private readonly string $username,
        #[Autowire(env: 'MAILMAN_API_PASSWORD')]
        private readonly string $password,
    ) {
    }

    /**
     * @param array<string, mixed>|null $data
     *
     * @return array<array-key, mixed>
     *
     * @throws RuntimeException
     */
    private function performMailmanRequest(
        string $uri,
        string $method = Request::METHOD_GET,
        ?array $data = null,
    ): array {
        $options = [
            'auth_basic' => [
                $this->username,
                $this->password,
            ],
            'timeout' => 600,
        ];

        // Data encoding is automatically set to `application/x-www-form-urlencoded` for "POST"-like requests.
        switch ($method) {
            case Request::METHOD_GET:
                $options['query'] = $data ?? [];
                break;
            case Request::METHOD_POST:
            case Request::METHOD_DELETE:
            case Request::METHOD_PATCH:
                if (null !== $data) {
                    $options['body'] = $data;
                }

                break;
        }

        // The client is lazy, so both the status code and the body have to be pulled before we know whether the
        // request actually made it to Mailman.
        try {
            $response = $this->httpClient->request(
                $method,
                $this->endpoint . $uri,
                $options,
            );

            $statusCode = $response->getStatusCode();
            // We want to try to parse everything that returned a 2xx status code, so suppress the client's own
            // exceptions on 3xx/4xx/5xx and decide below.
            $body = $response->getContent(false);
        } catch (TransportExceptionInterface $e) {
            throw new RuntimeException('Failed to send request: ' . $e->getMessage());
        }

        if (
            200 > $statusCode
            || 300 <= $statusCode
        ) {
            throw new RuntimeException('Request failed with status code: ' . $statusCode);
        }

        // If the body is empty, return empty array (e.g. for 204 status code).
        if ('' === $body) {
            return [];
        }

        if (!json_validate($body)) {
            throw new RuntimeException('Failed to parse JSON response: ' . json_last_error_msg());
        }

        return json_decode(
            $body,
            true,
        );
    }

    /**
     * Acquire sync lock.
     *
     * To ensure that the sync between GEWISDB and Mailman is as clean as possible, we need to acquire a global lock on
     * the mail list administration. This will prevent (if properly implemented and used) concurrent syncs from running.
     */
    private function acquireSyncLock(
        int $retries = 3,
        bool $renew = false,
    ): void {
        if (0 === $retries) {
            throw new RuntimeException('Unable to acquire sync lock for Mailman sync: timeout.');
        }

        if (
            $this->isSyncLocked()
            && !$renew
        ) {
            throw new RuntimeException('Unable to acquire sync lock for Mailman sync: locked by other process.');
        }

        if (
            !$this->isSyncLocked()
            && $renew
        ) {
            throw new RuntimeException('Unable to renew sync lock for Mailman sync: currently unlocked.');
        }

        $this->configService->setConfig(
            ConfigNamespaces::DatabaseMailman,
            'locked',
            new DateTime()->modify('+23 hours'),
        );

        if ($this->isSyncLocked()) {
            return;
        }

        $this->acquireSyncLock($retries - 1);
    }

    /**
     * Release sync lock.
     *
     * Releases the sync lock after the sync between GEWISDB and Mailman happened.
     */
    private function releaseSyncLock(): void
    {
        $this->configService->setConfig(
            ConfigNamespaces::DatabaseMailman,
            'locked',
            new DateTime(),
        );
    }

    /**
     * Get state of sync lock.
     */
    public function isSyncLocked(): bool
    {
        return $this->configService->getConfig(
            ConfigNamespaces::DatabaseMailman,
            'locked',
        ) > new DateTime();
    }

    /**
     * This functions syncs the mailing list membership of all mailing lists
     * Even if they don't have an associated mailman mailing list, to keep the code throughout the application the same
     */
    public function syncMembership(
        OutputInterface $output = new NullOutput(),
        bool $dryRun = false,
    ): void {
        $output->writeln('Processing pending memberships for Mailman mailing lists:');

        $this->assertMailmanHealthy();

        $this->acquireSyncLock();

        $lists = $this->mailingListRepository->findAll();

        foreach ($lists as $list) {
            if (!$list->hasMailmanList()) {
                continue;
            }

            $this->acquireSyncLock(renew: true);
            $this->syncMembershipSingle(
                $list,
                $output,
                $dryRun,
            );
        }

        $this->configService->setConfig(
            ConfigNamespaces::DatabaseMailman,
            'lastSync',
            new DateTime(),
        );

        $this->releaseSyncLock();
    }

    /**
     * This function syncs the membership of a mailing list
     */
    private function syncMembershipSingle(
        MailingList $dbList,
        OutputInterface $output,
        bool $dryRun,
    ): void {
        $dbMemberships = $dbList->getMailingListMemberships();

        $output->writeln(
            sprintf(
                '-> Syncing membership changes for <info>%s</info> (%s)',
                $dbList->getName(),
                $dbList->getMailmanList()->getMailmanId(),
            ),
            OutputInterface::VERBOSITY_VERBOSE,
        );

        $verifyTime = new DateTime()->sub(new DateInterval('P1D'));

        $listId = $dbList->getMailmanList()->getMailmanId();
        $knownMembers = $this->getMailmanListSubscriberEmails($listId);

        // Phase 1: Sync all pending changes from DB side
        // The order matters; we first process deletions, because we can have both be true
        // (e.g. when changing email addresses twice)
        foreach ($dbMemberships as $mailingListMember) {
            if (
                $mailingListMember->isToBeDeleted()
                || null === $mailingListMember->getMember()
            ) {
                $this->unsubscribeMemberFromMailingList(
                    mailingListMember: $mailingListMember,
                    output: $output,
                    dryRun: $dryRun,
                );
            } elseif ($mailingListMember->isToBeCreated()) {
                $this->subscribeMemberToMailingList(
                    mailingListMember: $mailingListMember,
                    output: $output,
                    dryRun: $dryRun,
                );
            } elseif ($mailingListMember->getLastSyncOn() < $verifyTime) {
                $this->verifyMemberOnMailingList(
                    mailingListMember: $mailingListMember,
                    output: $output,
                    dryRun: $dryRun,
                    knownMembers: $knownMembers,
                );
            }
        }

        // Phase 2: once per 24 hours
        if ($dbList->getMailmanList()->getLastCheck() > $verifyTime) {
            return;
        }

        // Sync all unknowns from mailman
        $this->fullCheckMailmanList(
            mailingList: $dbList,
            output: $output,
            dryRun: $dryRun,
        );
    }

    private function isMailmanHealthy(): bool
    {
        try {
            $data = $this->performMailmanRequest('system/versions');
        } catch (RuntimeException) {
            return false;
        }

        return isset($data['api_version']) && $data['api_version'] === $this->version;
    }

    private function assertMailmanHealthy(): void
    {
        $this->isMailmanHealthy() ||
            throw new RuntimeException('Mailman API is not healthy when performing mailman operation');
    }

    /**
     * @return array{
     *     mailmanLastFetch: ?DateTime,
     *     mailmanLastFetchOverdue: bool,
     *     mailmanLastSync: ?DateTime,
     * }
     */
    public function getFrontpageData(): array
    {
        $lastFetch = $this->getLastFetchTime();

        return [
            'mailmanLastFetch' => $lastFetch,
            'mailmanLastFetchOverdue' => self::isOverdue($lastFetch),
            'mailmanLastSync' => $this->configService->getConfig(
                ConfigNamespaces::DatabaseMailman,
                'lastSync',
                new DateTime('0001-01-01 00:00:00'),
            ),
        ];
    }

    /**
     * @return array<array-key,array{
     *     display_name: string,
     *     list_id: string,
     * }>
     */
    private function getAllListsFromMailman(): array
    {
        $lists = $this->performMailmanRequest('lists');

        if (
            isset($lists['total_size'])
            && 0 !== $lists['total_size']
        ) {
            return array_map(
                static fn ($list) => [
                    'list_id' => $list['list_id'],
                    'display_name' => $list['display_name'],
                ],
                $lists['entries'],
            );
        }

        return [];
    }

    /**
     * Fetch mailing lists from mailman and import them to the mailmanlist model in GEWISDB
     */
    public function fetchMailingLists(): void
    {
        $lists = $this->getAllListsFromMailman();

        foreach ($lists as $list) {
            $l = $this->mailmanMailingListRepository->find($list['list_id']);

            if (null === $l) {
                $l = new MailmanMailingList();
            }

            $l->setName($list['display_name']);
            $l->setMailmanId($list['list_id']);
            $l->setLastSeen();

            $this->mailmanMailingListRepository->persist($l);
        }
    }

    public function getMailingList(string $mailmanId): ?MailmanMailingList
    {
        return $this->mailmanMailingListRepository->find($mailmanId);
    }

    /**
     * Returns all recently seen mailing lists
     *
     * @return MailmanMailingList[]
     */
    public function getMailingLists(bool $activeOnly = true): array
    {
        if (false === $activeOnly) {
            return $this->mailmanMailingListRepository->findAll();
        }

        return $this->mailmanMailingListRepository->findActive();
    }

    /**
     * Get the last succesfull mailman sync (>= 1 list)
     */
    public function getLastFetchTime(): ?DateTime
    {
        return $this->mailmanMailingListRepository->getLastFetchTime();
    }

    /**
     * Whether a fetch that last succeeded at this time is late.
     *
     * Takes the time rather than reading it, so the caller that already has it does not ask for it again. It also
     * does not `add()` to it: that mutates the entity's own `DateTime`, and the caller is usually about to display
     * the very object being moved an hour and five minutes into the future.
     */
    private static function isOverdue(?DateTime $lastFetch): bool
    {
        if (null === $lastFetch) {
            return true;
        }

        return $lastFetch < new DateTime()->sub(new DateInterval('PT1H5M'));
    }

    /**
     * Subscribe a member to a mailing list.
     *
     * Unfortunately, this must be done one at the time as there is no mass-subscribe functionality in the API. See
     * https://gitlab.com/mailman/mailman/-/issues/419 for the open issue.
     */
    private function subscribeMemberToMailingList(
        MailingListMember $mailingListMember,
        OutputInterface $output,
        bool $dryRun,
    ): void {
        $member = $mailingListMember->getMember();
        $listId = $mailingListMember->getMailingList()->getMailmanList()->getMailmanId();

        // Create the data for the request
        $data = [
            'list_id' => $listId,
            'subscriber' => $mailingListMember->getEmail(),
            'display_name' => $member->getFullName(),
            'role' => self::MM_ROLE_MEMBER,
            'pre_verified' => true,
            'pre_confirmed' => true,
            'pre_approved' => true,
            // Mailman's own welcome message is never wanted: a subscription here follows from something the
            // association already told the member about -- a registration, a renewal, a change they asked for -- and
            // the mail Mailman would send says none of that. This asked for one on every subscription.
            'send_welcome_message' => false,
            'delivery_mode' => self::MM_DELIVERYMODE_REGULAR,
            'delivery_status' => self::MM_DELIVERYSTATUS_ENABLED,
        ];

        $output->writeln(
            sprintf(
                '--> Subscribing %s to %s',
                $data['subscriber'],
                $data['list_id'],
            ),
            OutputInterface::VERBOSITY_VERY_VERBOSE,
        );

        if ($dryRun) {
            return;
        }

        // Send the request to the Mailman API
        $mailingListMember->setLastSyncOn();
        $response = $this->performMailmanRequest(
            uri: 'members',
            method: Request::METHOD_POST,
            data: $data,
        );

        // Check if the request was successful
        // Status code 201 + empty array means success
        if ([] === $response) {
            $mailingListMember->setLastSyncSuccess(true);
            $mailingListMember->setToBeCreated(false);
        } else {
            $mailingListMember->setLastSyncSuccess(false);
        }

        $this->mailingListMemberRepository->persist($mailingListMember);
    }

    private function unsubscribeMemberFromMailingList(
        MailingListMember $mailingListMember,
        OutputInterface $output,
        bool $dryRun,
    ): void {
        $listId = $mailingListMember->getMailingList()->getMailmanList()->getMailmanId();

        $data = [
            'list_id' => $listId,
            'subscriber' => $mailingListMember->getEmail(),
            'role' => self::MM_ROLE_MEMBER,
        ];

        $response = $this->performMailmanRequest(
            'members/find',
            data: $data,
        );

        // There should be at most one entry
        if (1 < $response['total_size']) {
            throw new RuntimeException(
                sprintf(
                    'Found more than one member %s with role %s on list %s',
                    $data['subscriber'],
                    $data['role'],
                    $data['list_id'],
                ),
            );
        }

        $output->writeln(
            sprintf(
                '--> Removing %s from %s',
                $data['subscriber'],
                $data['list_id'],
            ),
            OutputInterface::VERBOSITY_VERY_VERBOSE,
        );

        if ($dryRun) {
            return;
        }

        $member = $mailingListMember->getMember();

        if (
            null !== $member
            && false === $mailingListMember->isToBeDeleted()
        ) {
            $this->auditService->persist(
                AuditMailingListMembership::create(
                    MailingListMemberAction::Remove,
                    MailingListMemberOrigin::SyncMailman,
                    $member,
                    $mailingListMember->getMailingList(),
                    $mailingListMember->getEmail(),
                ),
            );
        }

        if (1 === $response['total_size']) {
            $memberId = $response['entries'][0]['member_id'];

            $this->performMailmanRequest(
                'members/' . rawurlencode($memberId),
                method: Request::METHOD_DELETE,
            );
        }

        $this->mailingListMemberRepository->remove($mailingListMember);
    }

    /**
     * This function verifies that a member is still on a given mailing list
     * and if not, removes the mailinglistMember entity
     *
     * Optionally accepts an array of known members
     *
     * @param string[] $knownMembers A list of email addresses guaranteed to be on the list
     */
    private function verifyMemberOnMailingList(
        MailingListMember $mailingListMember,
        OutputInterface $output,
        bool $dryRun,
        array $knownMembers,
    ): void {
        // If there is no associated mailman list, assume this is right
        if (!$mailingListMember->getMailingList()->hasMailmanList()) {
            throw new LogicException('Cannot verify mailing list subscription for non-mailman list');
        }

        $listId = $mailingListMember->getMailingList()->getMailmanList()->getMailmanId();

        if (
            in_array(
                $mailingListMember->getEmail(),
                $knownMembers,
            )
        ) {
            $mailingListMember->setLastSyncOn();
            $this->mailingListMemberRepository->persist($mailingListMember);

            return;
        }

        $output->writeln(
            sprintf(
                '--> %s is not in the list of known members of %s, verifying in mailman',
                $mailingListMember->getEmail(),
                $listId,
            ),
            OutputInterface::VERBOSITY_VERY_VERBOSE,
        );

        $data = [
            'list_id' => $listId,
            'subscriber' => $mailingListMember->getEmail(),
            'role' => self::MM_ROLE_MEMBER,
        ];

        $response = $this->performMailmanRequest(
            'members/find',
            data: $data,
        );

        // There should be at most one entry
        if (1 < $response['total_size']) {
            throw new RuntimeException(
                sprintf(
                    'Found more than one member %s with role %s on list %s',
                    $data['subscriber'],
                    $data['role'],
                    $data['list_id'],
                ),
            );
        }

        if (1 === $response['total_size']) {
            $mailingListMember->setLastSyncOn();
            $this->mailingListMemberRepository->persist($mailingListMember);

            return;
        }

        $output->writeln(
            sprintf(
                '--> %s has disappeared from %s, removing db entry',
                $data['subscriber'],
                $data['list_id'],
            ),
            OutputInterface::VERBOSITY_VERY_VERBOSE,
        );

        if ($dryRun) {
            return;
        }

        $member = $mailingListMember->getMember();

        if (
            null !== $member
            && false === $mailingListMember->isToBeDeleted()
        ) {
            $this->auditService->persist(
                AuditMailingListMembership::create(
                    MailingListMemberAction::Remove,
                    MailingListMemberOrigin::SyncMailman,
                    $member,
                    $mailingListMember->getMailingList(),
                    $mailingListMember->getEmail(),
                ),
            );
        }

        $this->mailingListMemberRepository->remove($mailingListMember);
    }

    /**
     * Function to process 'new' or unknown mailman registrations
     * When member known, adds to DB. When member unknown, removes from mailman
     */
    private function fullCheckMailmanList(
        MailingList $mailingList,
        OutputInterface $output,
        bool $dryRun,
    ): void {
        $mmList = $mailingList->getMailmanList();
        $membersDB = $mailingList->getMailingListMemberships();
        $listId = $mailingList->getMailmanList()->getMailmanId();

        $memberEmails = array_flip(array_map(
            static fn (MailingListMember $member): string => $member->getEmail(),
            $membersDB->toArray(),
        ));

        $entries = $this->getMailmanListSubscriberEntries($listId);
        foreach ($entries as $entry) {
            if (isset($memberEmails[$entry['email']])) {
                continue;
            }

            $foundMember = $this->memberRepository->findByEmail($entry['email']);

            if (null === $foundMember) {
                $output->writeln(
                    sprintf(
                        '--> Removing unknown email %s from %s',
                        $entry['email'],
                        $listId,
                    ),
                    OutputInterface::VERBOSITY_VERY_VERBOSE,
                );

                if (!$dryRun) {
                    $this->performMailmanRequest(
                        'members/' . rawurlencode($entry['member_id']),
                        method: Request::METHOD_DELETE,
                    );
                }
            } else {
                $output->writeln(
                    sprintf(
                        '--> Found %s on %s, updating database',
                        $entry['email'],
                        $listId,
                    ),
                    OutputInterface::VERBOSITY_VERY_VERBOSE,
                );

                if (!$dryRun) {
                    $this->auditService->persist(
                        AuditMailingListMembership::create(
                            MailingListMemberAction::Add,
                            MailingListMemberOrigin::SyncMailman,
                            $foundMember,
                            $mailingList,
                            $entry['email'],
                        ),
                    );

                    $mailingListMember = new MailingListMember();
                    $mailingListMember->setMailingList($mailingList);
                    $mailingListMember->setMember($foundMember);
                    $mailingListMember->setEmail($entry['email']);
                    $mailingListMember->setToBeCreated(false);
                    $this->mailingListMemberRepository->persist($mailingListMember);
                }
            }
        }

        if ($dryRun) {
            return;
        }

        $mmList->setLastCheck();
        $this->mailmanMailingListRepository->persist($mmList);
    }

    /**
     * Function to get all current subscribers to a list in mailman
     * Particularly useful when comparing the entire list
     *
     * @return array<array-key,array{
     *  email: string,
     *  list_id : string,
     *  member_id: string,
     *  role: string,
     * }>
     */
    private function getMailmanListSubscriberEntries(string $listId): array
    {
        $data = [
            'list_id' => $listId,
            'role' => self::MM_ROLE_MEMBER,
        ];

        // By default this response is not paginated, which is what we want here
        $response = $this->performMailmanRequest(
            'members/find',
            data: $data,
        );

        if ($response['total_size'] > 0) {
            return $response['entries'];
        }

        return [];
    }

    /**
     * Function to get all email addresses currently subscribed to a given mailman list
     *
     * @return string[]
     */
    private function getMailmanListSubscriberEmails(string $listId): array
    {
        return array_map(
            static fn (array $entry) => $entry['email'],
            $this->getMailmanListSubscriberEntries($listId),
        );
    }
}
