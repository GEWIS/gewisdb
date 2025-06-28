<?php

declare(strict_types=1);

namespace Database\Service;

use Application\Model\Enums\ConfigNamespaces;
use Application\Service\Config as ConfigService;
use Database\Mapper\MailingListMember as MailingListMemberMapper;
use Database\Mapper\MailmanMailingList as MailmanMailingListMapper;
use Database\Model\MailingListMember as MailingListMemberModel;
use Database\Model\MailmanMailingList as MailmanMailingListModel;
use DateInterval;
use DateTime;
use Laminas\Http\Client;
use Laminas\Http\Client\Adapter\Curl;
use Laminas\Http\Request;
use RuntimeException;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

use function array_map;
use function json_decode;
use function json_last_error_msg;
use function json_validate;
use function rawurlencode;
use function sprintf;

class Mailman
{
    private const MM_ROLE_NONMEMBER = 'nonmember';
    private const MM_ROLE_MEMBER = 'member';
    private const MM_ROLE_MODERATOR = 'moderator';
    private const MM_ROLE_OWNER = 'owner';

    private const MM_DELIVERYMODE_REGULAR = 'regular';
    private const MM_DELIVERYMODE_DIGESTS_MIME = 'mime_digests';
    private const MM_DELIVERYMODE_DIGESTS_PLAIN = 'plaintext_digests';
    private const MM_DELLIVERYMODE_DIGESTS_SUMMARY = 'summary_digests';

    private const MM_DELIVERYSTATUS_ENABLED = 'enabled';
    private const MM_DELIVERYSTATUS_DISABLED_BY_USER = 'by_user';
    private const MM_DELIVERYSTATUS_DISABLED_BY_BOUNCES = 'by_bounces';
    private const MM_DELIVERYSTATUS_DISABLED_BY_MODERATOR = 'by_moderator';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function __construct(
        private readonly MailmanMailingListMapper $mailmanMailingListMapper,
        private readonly MailingListMemberMapper $mailingListMemberMapper,
        private readonly ConfigService $configService,
        private readonly array $mailmanConfig,
    ) {
    }

    /**
     * @throws RuntimeException
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    private function performMailmanRequest(
        string $uri,
        string $method = Request::METHOD_GET,
        ?array $data = null,
    ): array {
        $client = new Client();

        $client->setAdapter(Curl::class)
            ->setAuth($this->mailmanConfig['username'], $this->mailmanConfig['password'])
            ->setMethod($method)
            ->setUri($this->mailmanConfig['endpoint'] . $uri);

        // Data encoding is automatically set to `application/x-www-form-urlencoded` for "POST"-like requests.
        switch ($method) {
            case Request::METHOD_GET:
                if (null === $data) {
                    $data = [];
                }

                $client->setParameterGet($data);
                break;
            case Request::METHOD_POST:
            case Request::METHOD_DELETE:
            case Request::METHOD_PATCH:
                if (null !== $data) {
                    $client->setParameterPost($data);
                }

                break;
        }

        try {
            $response = $client->send();
        } catch (RuntimeException $e) {
            throw new RuntimeException('Failed to send request: ' . $e->getMessage());
        }

        // We want to try to parse everything that returned a 2xx status code.
        if (!$response->isSuccess()) {
            throw new RuntimeException('Request failed with status code: ' . $response->getStatusCode());
        }

        // Parse body of response.
        $body = $response->getBody();

        // If the body is empty, return empty array (e.g. for 204 status code).
        if ('' === $body) {
            return [];
        }

        if (!json_validate($body)) {
            throw new RuntimeException('Failed to parse JSON response: ' . json_last_error_msg());
        }

        return json_decode($body, true);
    }

    /**
     * Acquire sync lock.
     *
     * To ensure that the sync between GEWISDB and Mailman is as clean as possible, we need to acquire a global lock on
     * the mail list administration. This will prevent (if properly implemented and used) the secretary from modifying
     * any mailing list memberships.
     */
    private function acquireSyncLock(int $retries = 3): void
    {
        if (0 === $retries) {
            throw new RuntimeException('Unable to acquire sync lock for Mailman sync: timeout.');
        }

        if ($this->isSyncLocked()) {
            throw new RuntimeException('Unable to acquire sync lock for Mailman sync: locked by other process.');
        }

        $this->configService->setConfig(ConfigNamespaces::DatabaseMailman, 'locked', true);

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
        $this->configService->setConfig(ConfigNamespaces::DatabaseMailman, 'locked', false);
    }

    /**
     * Get state of sync lock.
     */
    public function isSyncLocked(): bool
    {
        return $this->configService->getConfig(ConfigNamespaces::DatabaseMailman, 'locked', false);
    }

    /**
     * This functions syncs the mailing list membership of all mailing lists
     */
    public function syncMembership(
        OutputInterface $output = new NullOutput(),
        bool $dryRun = false,
    ): void {
        $lists = $this->mailmanMailingListMapper->findActive();

        foreach ($lists as $list) {
            if (null === $list->getMailingList()) {
                continue;
            }

            $this->syncMembershipSingle($list, $output, $dryRun);
        }
    }

    /**
     * This function syncs the membership of a mailing list
     */
    private function syncMembershipSingle(
        MailmanMailingListModel $mmList,
        OutputInterface $output,
        bool $dryRun,
    ): void {
        $dbList = $mmList->getMailingList();
        $dbMemberships = $dbList->getMailingListMemberships();

        $output->writeln(
            sprintf(
                '-> Syncing membership changes for <info>%s</info> (%s)',
                $dbList->getName(),
                $mmList->getMailmanId(),
            ),
            OutputInterface::VERBOSITY_VERBOSE,
        );

        // Phase 1: Sync all pending changes from DB side
        foreach ($dbMemberships as $mailingListMember) {
            if ($mailingListMember->isToBeDeleted()) {
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
                    sendWelcomeEmail: true,
                );
            }
        }

        // Phase 2: Voor alle to be created, perform creation
        // TODO
    }

    public function isMailmanHealthy(): bool
    {
        try {
            $data = $this->performMailmanRequest('system/versions');
        } catch (RuntimeException) {
            return false;
        }

        return isset($data['api_version']) && $data['api_version'] === $this->mailmanConfig['version'];
    }

    /**
     * @return array{
     *     mailmanLastFetch: ?DateTime,
     *     mailmanLastFetchOverdue: bool,
     *     mailmanLastSync: DateTime,
     *     mailmanChangesPending: array{
     *       creations: int,
     *       deletions: int,
     *     },
     * }
     */
    public function getFrontpageData(): array
    {
        return [
            'mailmanLastFetch' => $this->getLastFetchTime(),
            'mailmanLastFetchOverdue' => $this->isLastFetchOverdue(),
            'mailmanLastSync' => new DateTime(), //TODO
            'mailmanChangesPending' => [
                'creations' => $this->mailingListMemberMapper->countPendingCreation(),
                'deletions' => $this->mailingListMemberMapper->countPendingDeletion(),
            ],
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
            $l = $this->mailmanMailingListMapper->find($list['list_id']);

            if (null === $l) {
                $l = new MailmanMailingListModel();
            }

            $l->setName($list['display_name']);
            $l->setMailmanId($list['list_id']);
            $l->setLastSeen();

            $this->mailmanMailingListMapper->persist($l);
        }
    }

    public function getMailingList(string $mailmanId): ?MailmanMailingListModel
    {
        return $this->mailmanMailingListMapper->find($mailmanId);
    }

    /**
     * Returns all recently seen mailing lists
     *
     * @return MailmanMailingListModel[]
     */
    public function getMailingLists(bool $activeOnly = true): array
    {
        if (false === $activeOnly) {
            return $this->mailmanMailingListMapper->findAll();
        }

        return $this->mailmanMailingListMapper->findActive();
    }

    /**
     * Get the last succesfull mailman sync (>= 1 list)
     */
    public function getLastFetchTime(): ?DateTime
    {
        return $this->mailmanMailingListMapper->getLastFetchTime();
    }

    public function isLastFetchOverdue(): bool
    {
        $lastFetch = $this->getLastFetchTime();
        if (null === $lastFetch) {
            return true;
        }

        return $lastFetch->add(new DateInterval('PT1H5M')) < new DateTime();
    }

    /**
     * Subscribe a member to a mailing list.
     *
     * Unfortunately, this must be done one at the time as there is no mass-subscribe functionality in the API. See
     * https://gitlab.com/mailman/mailman/-/issues/419 for the open issue.
     */
    private function subscribeMemberToMailingList(
        MailingListMemberModel $mailingListMember,
        OutputInterface $output,
        bool $dryRun,
        bool $sendWelcomeEmail,
    ): void {
        $member = $mailingListMember->getMember();
        $listId = $mailingListMember->getMailingList()->getMailmanList()->getMailmanId();

        // Create the data for the request
        $data = [
            'list_id' => $listId,
            'subscriber' => $member->getEmail(),
            'display_name' => $member->getFullName(),
            'role' => self::MM_ROLE_MEMBER,
            'pre_verified' => true,
            'pre_confirmed' => true,
            'pre_approved' => true,
            'send_welcome_message' => $sendWelcomeEmail,
            'delivery_mode' => self::MM_DELIVERYMODE_REGULAR,
            'delivery_status' => self::MM_DELIVERYSTATUS_ENABLED,
        ];

        $output->writeln(
            sprintf(
                '--> Subscribing %s to %s (send_welcome_message=%s)',
                $data['subscriber'],
                $data['list_id'],
                (int) $data['send_welcome_message'],
            ),
            OutputInterface::VERBOSITY_VERY_VERBOSE,
        );

        if ($dryRun) {
            return;
        }

        // Send the request to the Mailman API
        $mailingListMember->setLastSyncOn(new DateTime());
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

        $this->mailingListMemberMapper->persist($mailingListMember);
    }

    private function unsubscribeMemberFromMailingList(
        MailingListMemberModel $mailingListMember,
        OutputInterface $output,
        bool $dryRun,
    ): void {
        $member = $mailingListMember->getMember();
        $listId = $mailingListMember->getMailingList()->getMailmanList()->getMailmanId();

        $data = [
            'list_id' => $listId,
            'subscriber' => $member->getEmail(),
            'role' => self::MM_ROLE_MEMBER,
        ];

        $response = $this->performMailmanRequest('members/find', data: $data);

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

        if (1 === $response['total_size']) {
            $memberId = $response['entries'][0]['member_id'];

            $this->performMailmanRequest('members/' . rawurlencode($memberId), method: Request::METHOD_DELETE);
        }

        $this->mailingListMemberMapper->remove($mailingListMember);
    }

    public function massUnsubscribeMembersFromMailingList(): void
    {
    }
}
