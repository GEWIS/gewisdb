<?php

declare(strict_types=1);

namespace Database\Service;

use Application\Model\Enums\ConfigNamespaces;
use Application\Service\Config as ConfigService;
use Database\Mapper\MailingList as MailingListMapper;
use Database\Mapper\MailingListMember as MailingListMemberMapper;
use Database\Mapper\ListmonkMailingList as ListmonkMailingListMapper;
use Database\Mapper\Member as MemberMapper;
use Database\Model\MailingList as MailingListModel;
use Database\Model\MailingListMember as MailingListMemberModel;
use Database\Model\ListmonkMailingList as ListmonkMailingListModel;
use DateInterval;
use DateTime;
use Laminas\Http\Client;
use Laminas\Http\Client\Adapter\Curl;
use Laminas\Http\Request;
use LogicException;
use RuntimeException;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

use function array_map;
use function in_array;
use function json_decode;
use function json_last_error_msg;
use function json_validate;
use function sprintf;

class Listmonk
{
    private const string LM_STATUS_CONFIRMED = 'confirmed';
    private const string LM_STATUS_UNSUBSCRIBED = 'unsubscribed';
    private const string LM_STATUS_UCONFIRMED = 'unconfirmed';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function __construct(
        private readonly MailingListMapper $mailingListMapper,
        private readonly ListmonkMailingListMapper $listmonkMailingListMapper,
        private readonly MailingListMemberMapper $mailingListMemberMapper,
        private readonly MemberMapper $memberMapper,
        private readonly ConfigService $configService,
        private readonly array $listmonkConfig,
    ) {
    }

    /**
     * @throws RuntimeException
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    private function performListmonkRequest(
        string $uri,
        string $method = Request::METHOD_GET,
        ?array $data = null,
    ): array {
        $client = new Client();

        $client->setAdapter(Curl::class)
            ->setAuth($this->listmonkConfig['username'], $this->listmonkConfig['password'])
            ->setMethod($method)
            ->setOptions([
                'timeout' => 600,
            ])
            ->setUri($this->listmonkConfig['endpoint'] . $uri);

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
            case Request::METHOD_PUT:
                if (null !== $data) {
                    $client->setRawBody(json_encode($data));
                    $client->getRequest()->getHeaders()->addHeaders([
                        'Content-Type' => 'application/json',
                    ]);
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
     * To ensure that the sync between GEWISDB and Listmonk is as clean as possible, we need to acquire a global lock on
     * the mail list administration. This will prevent (if properly implemented and used) concurrent syncs from running.
     */
    private function acquireSyncLock(
        int $retries = 3,
        bool $renew = false,
    ): void {
        if (0 === $retries) {
            throw new RuntimeException('Unable to acquire sync lock for Listmonk sync: timeout.');
        }

        if ($this->isSyncLocked() && !$renew) {
            throw new RuntimeException('Unable to acquire sync lock for Listmonk sync: locked by other process.');
        }

        if (!$this->isSyncLocked() && $renew) {
            throw new RuntimeException('Unable to renew sync lock for Listmonk sync: currently unlocked.');
        }

        $this->configService->setConfig(
            ConfigNamespaces::DatabaseListmonk,
            'locked',
            (new DateTime())->modify('+23 hours'),
        );

        if ($this->isSyncLocked()) {
            return;
        }

        $this->acquireSyncLock($retries - 1);
    }

    /**
     * Release sync lock.
     *
     * Releases the sync lock after the sync between GEWISDB and Listmonk happened.
     */
    private function releaseSyncLock(): void
    {
        $this->configService->setConfig(ConfigNamespaces::DatabaseListmonk, 'locked', new DateTime());
    }

    /**
     * Get state of sync lock.
     */
    public function isSyncLocked(): bool
    {
        return $this->configService->getConfig(ConfigNamespaces::DatabaseListmonk, 'locked') > new DateTime();
    }

    /**
     * This functions syncs the mailing list membership of all mailing lists
     * Even if they don't have an associated listmonk mailing list, to keep the code throughout the application the same
     */
    public function syncMembership(
        OutputInterface $output = new NullOutput(),
        bool $dryRun = false,
    ): void {
        $this->assertListmonkHealthy();

        $this->acquireSyncLock();

        $lists = $this->mailingListMapper->findAll();

        foreach ($lists as $list) {
            $this->acquireSyncLock(renew: true);
            $this->syncMembershipSingle($list, $output, $dryRun);
        }

        $this->configService->setConfig(ConfigNamespaces::DatabaseListmonk, 'lastSync', new DateTime());

        $this->releaseSyncLock();
    }

    /**
     * This function syncs the membership of a mailing list
     */
    private function syncMembershipSingle(
        MailingListModel $dbList,
        OutputInterface $output,
        bool $dryRun,
    ): void {
        $dbMemberships = $dbList->getMailingListMemberships();

        $output->writeln(
            sprintf(
                '-> Syncing membership changes for <info>%s</info> (%s)',
                $dbList->getName(),
                $dbList->hasListmonkList() ? $dbList->getListmonkList()->getListmonkId() : 'local',
            ),
            OutputInterface::VERBOSITY_VERBOSE,
        );

        $verifyTime = (new DateTime())->sub(new DateInterval('P1D'));

        $isListmonkList = $dbList->hasListmonkList();
        if ($isListmonkList) {
            $listId = $dbList->getListmonkList()->getListmonkId();
            $knownMembers = $this->getListmonkListSubscriberEmails($listId);
        } else {
            // This is to satisfy psalm, observe that it is not needed
            $knownMembers = [];
        }

        // Phase 1: Sync all pending changes from DB side
        // The order matters; we first process deletions, because we can have both be true
        // (e.g. when changing email addresses twice)
        foreach ($dbMemberships as $mailingListMember) {
            if ($mailingListMember->isToBeDeleted() || null === $mailingListMember->getMember()) {
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
            } elseif ($isListmonkList && $mailingListMember->getLastSyncOn() < $verifyTime) {
                $this->verifyMemberOnMailingList(
                    mailingListMember: $mailingListMember,
                    output: $output,
                    dryRun: $dryRun,
                    knownMembers: $knownMembers,
                );
            }
        }

        // The rest only applies to mailing lists that have a listmonk list
        if (!$dbList->hasListmonkList()) {
            return;
        }

        // Phase 2: once per 24 hours
        if ($dbList->getListmonkList()->getLastCheck() > $verifyTime) {
            return;
        }

        // Sync all unknowns from listmonk
        $this->fullCheckListmonkList(
            mailingList: $dbList,
            output: $output,
            dryRun: $dryRun,
        );
    }

    private function isListmonkHealthy(): bool
    {
        try {
            $data = $this->performListmonkRequest('health');
        } catch (RuntimeException) {
            return false;
        }

        return isset($data['data']) && true === $data['data'];
    }

    private function assertListmonkHealthy(): void
    {
        $this->isListmonkHealthy() ||
            throw new RuntimeException('Listmonk API is not healthy when performing listmonk operation');
    }

    /**
     * @return array{
     *     listmonkLastFetch: ?DateTime,
     *     listmonkLastFetchOverdue: bool,
     *     listmonkLastSync: ?DateTime,
     *     listmonkChangesPending: array{
     *       creations: int,
     *       deletions: int,
     *     },
     * }
     */
    public function getFrontpageData(): array
    {
        return [
            'listmonkLastFetch' => $this->getLastFetchTime(),
            'listmonkLastFetchOverdue' => $this->isLastFetchOverdue(),
            'listmonkLastSync' => $this->configService->getConfig(
                ConfigNamespaces::DatabaseListmonk,
                'lastSync',
                new DateTime('0001-01-01 00:00:00'),
            ),
            'listmonkChangesPending' => [
                'creations' => $this->mailingListMemberMapper->countPendingCreation(),
                'deletions' => $this->mailingListMemberMapper->countPendingDeletion(),
            ],
        ];
    }

    /**
     * @return array<array-key,array{
     *     name: string,
     *     uuid: string,
     * }>
     */
    private function getAllListsFromListmonk(): array
    {
        $lists = $this->performListmonkRequest('lists?per_page=all');

        if (
            isset($lists['data']['results'])
            && 0 !== $lists['data']['total']
        ) {
            return array_map(
                static fn ($list) => [
                    'uuid' => $list['uuid'],
                    'name' => $list['name'],
                ],
                $lists['data']['results'],
            );
        }

        return [];
    }

    /**
     * Fetch mailing lists from listmonk and import them to the listmonklist model in GEWISDB
     */
    public function fetchMailingLists(): void
    {
        $lists = $this->getAllListsFromListmonk();

        foreach ($lists as $list) {
            $l = $this->listmonkMailingListMapper->find($list['uuid']);

            if (null === $l) {
                $l = new ListmonkMailingListModel();
            }

            $l->setName($list['name']);
            $l->setListmonkId($list['uuid']);
            $l->setLastSeen();

            $this->listmonkMailingListMapper->persist($l);
        }
    }

    public function getMailingList(string $listmonkId): ?ListmonkMailingListModel
    {
        return $this->listmonkMailingListMapper->find($listmonkId);
    }

    /**
     * Returns all recently seen mailing lists
     *
     * @return ListmonkMailingListModel[]
     */
    public function getMailingLists(bool $activeOnly = true): array
    {
        if (false === $activeOnly) {
            return $this->listmonkMailingListMapper->findAll();
        }

        return $this->listmonkMailingListMapper->findActive();
    }

    /**
     * Get the last succesfull listmonk sync (>= 1 list)
     */
    public function getLastFetchTime(): ?DateTime
    {
        return $this->listmonkMailingListMapper->getLastFetchTime();
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
     */
    private function subscribeMemberToMailingList(
        MailingListMemberModel $mailingListMember,
        OutputInterface $output,
        bool $dryRun,
    ): void {
        // If there is no associated listmonk list, assume processed
        if (!$mailingListMember->getMailingList()->hasListmonkList()) {
            $mailingListMember->setLastSyncSuccess(true);
            $mailingListMember->setToBeCreated(false);
            $this->mailingListMemberMapper->persist($mailingListMember);

            return;
        }

        $member = $mailingListMember->getMember();
        $listId = $mailingListMember->getMailingList()->getListmonkList()->getListmonkId();

        // First, check if subscriber exists
        $subscriberData = [
            'email' => $mailingListMember->getEmail(),
        ];

        $existingSubscribers = $this->performListmonkRequest('subscribers', Request::METHOD_GET, [
            'query' => sprintf("email='%s'", $subscriberData['email']),
        ]);

        $subscriberId = null;
        if (isset($existingSubscribers['data']['results'][0]['id'])) {
            $subscriberId = $existingSubscribers['data']['results'][0]['id'];
        }

        // If subscriber doesn't exist, create them
        if (null === $subscriberId) {
            $newSubscriber = [
                'email' => $mailingListMember->getEmail(),
                'name' => $member->getFullName(),
                'status' => self::LM_STATUS_CONFIRMED,
                'list_uuids' => [$listId],
            ];

            $output->writeln(
                sprintf(
                    '--> Creating subscriber %s and adding to list %s',
                    $newSubscriber['email'],
                    $listId,
                ),
                OutputInterface::VERBOSITY_VERY_VERBOSE,
            );

            if ($dryRun) {
                return;
            }

            $response = $this->performListmonkRequest(
                uri: 'subscribers',
                method: Request::METHOD_POST,
                data: $newSubscriber,
            );
        } else {
            // Subscriber exists, add to list
            $output->writeln(
                sprintf(
                    '--> Adding existing subscriber %s to list %s',
                    $subscriberData['email'],
                    $listId,
                ),
                OutputInterface::VERBOSITY_VERY_VERBOSE,
            );

            if ($dryRun) {
                return;
            }

            $response = $this->performListmonkRequest(
                uri: sprintf('subscribers/lists/%s', $listId),
                method: Request::METHOD_PUT,
                data: [
                    'action' => 'add',
                    'query' => sprintf("id=%d", $subscriberId),
                ],
            );
        }

        $mailingListMember->setLastSyncOn();
        $mailingListMember->setLastSyncSuccess(true);
        $mailingListMember->setToBeCreated(false);
        $this->mailingListMemberMapper->persist($mailingListMember);
    }

    private function unsubscribeMemberFromMailingList(
        MailingListMemberModel $mailingListMember,
        OutputInterface $output,
        bool $dryRun,
    ): void {
        // If there is no associated listmonk list, assume processed
        if (!$mailingListMember->getMailingList()->hasListmonkList()) {
            $this->mailingListMemberMapper->remove($mailingListMember);

            return;
        }

        $listId = $mailingListMember->getMailingList()->getListmonkList()->getListmonkId();
        $email = $mailingListMember->getEmail();

        $output->writeln(
            sprintf(
                '--> Removing %s from %s',
                $email,
                $listId,
            ),
            OutputInterface::VERBOSITY_VERY_VERBOSE,
        );

        if ($dryRun) {
            return;
        }

        // Find the subscriber by email
        $subscribers = $this->performListmonkRequest('subscribers', Request::METHOD_GET, [
            'query' => sprintf("email='%s'", $email),
        ]);

        if (isset($subscribers['data']['results'][0]['id'])) {
            $subscriberId = $subscribers['data']['results'][0]['id'];

            // Remove subscriber from the specific list
            $this->performListmonkRequest(
                uri: sprintf('subscribers/lists/%s', $listId),
                method: Request::METHOD_PUT,
                data: [
                    'action' => 'remove',
                    'query' => sprintf("id=%d", $subscriberId),
                ],
            );
        }

        $this->mailingListMemberMapper->remove($mailingListMember);
    }

    /**
     * This function verifies that a member is still on a given mailing list
     * and if not, removes the mailinglistMemberModel
     *
     * Optionally accepts an array of known members
     *
     * @param string[] $knownMembers A list of email addresses guaranteed to be on the list
     */
    private function verifyMemberOnMailingList(
        MailingListMemberModel $mailingListMember,
        OutputInterface $output,
        bool $dryRun,
        array $knownMembers,
    ): void {
        // If there is no associated listmonk list, assume this is right
        if (!$mailingListMember->getMailingList()->hasListmonkList()) {
            throw new LogicException('Cannot verify mailing list subscription for non-listmonk list');
        }

        $listId = $mailingListMember->getMailingList()->getListmonkList()->getListmonkId();

        if (in_array($mailingListMember->getEmail(), $knownMembers)) {
            $mailingListMember->setLastSyncOn();
            $this->mailingListMemberMapper->persist($mailingListMember);

            return;
        }

        $output->writeln(
            sprintf(
                '--> %s is not in the list of known members of %s, verifying in listmonk',
                $mailingListMember->getEmail(),
                $listId,
            ),
            OutputInterface::VERBOSITY_VERY_VERBOSE,
        );

        // Check if subscriber is on the list by getting their details
        $subscribers = $this->performListmonkRequest('subscribers', Request::METHOD_GET, [
            'query' => sprintf("email='%s' AND lists.uuid='%s'", $mailingListMember->getEmail(), $listId),
        ]);

        if (isset($subscribers['data']['results'][0])) {
            $mailingListMember->setLastSyncOn();
            $this->mailingListMemberMapper->persist($mailingListMember);

            return;
        }

        $output->writeln(
            sprintf(
                '--> %s has disappeared from %s, removing db entry',
                $mailingListMember->getEmail(),
                $listId,
            ),
            OutputInterface::VERBOSITY_VERY_VERBOSE,
        );

        if ($dryRun) {
            return;
        }

        $this->mailingListMemberMapper->remove($mailingListMember);
    }

    /**
     * Function to process 'new' or unknown listmonk registrations
     * When member known, adds to DB. When member unknown, removes from listmonk
     */
    private function fullCheckListmonkList(
        MailingListModel $mailingList,
        OutputInterface $output,
        bool $dryRun,
    ): void {
        $lmList = $mailingList->getListmonkList();
        $membersDB = $mailingList->getMailingListMemberships();
        $listId = $mailingList->getListmonkList()->getListmonkId();

        // Get all subscribers for this list
        $subscribers = $this->performListmonkRequest('subscribers', Request::METHOD_GET, [
            'list_id' => $listId,
            'per_page' => 'all',
        ]);

        if (isset($subscribers['data']['results'])) {
            foreach ($subscribers['data']['results'] as $subscriber) {
                $found = false;
                foreach ($membersDB as $member) {
                    if ($member->getEmail() !== $subscriber['email']) {
                        continue;
                    }

                    $found = true;
                }

                $foundMember = $this->memberMapper->findByEmail($subscriber['email']);

                if (!$found && null === $foundMember) {
                    $output->writeln(
                        sprintf(
                            '--> Removing unknown email %s from %s',
                            $subscriber['email'],
                            $listId,
                        ),
                        OutputInterface::VERBOSITY_VERY_VERBOSE,
                    );

                    if (!$dryRun) {
                        // Remove subscriber from the list
                        $this->performListmonkRequest(
                            uri: sprintf('subscribers/lists/%s', $listId),
                            method: Request::METHOD_PUT,
                            data: [
                                'action' => 'remove',
                                'query' => sprintf("id=%d", $subscriber['id']),
                            ],
                        );
                    }
                } elseif (!$found) {
                    $output->writeln(
                        sprintf(
                            '--> Found %s on %s, updating database',
                            $subscriber['email'],
                            $listId,
                        ),
                        OutputInterface::VERBOSITY_VERY_VERBOSE,
                    );

                    if (!$dryRun) {
                        $mailingListMember = new MailingListMemberModel();
                        $mailingListMember->setMailingList($mailingList);
                        $mailingListMember->setMember($foundMember);
                        $mailingListMember->setEmail($subscriber['email']);
                        $mailingListMember->setToBeCreated(false);
                        $this->mailingListMemberMapper->persist($mailingListMember);
                    }
                }
            }
        }

        if ($dryRun) {
            return;
        }

        $lmList->setLastCheck();
        $this->listmonkMailingListMapper->persist($lmList);
    }

    /**
     * Function to get all email addresses currently subscribed to a given listmonk list
     *
     * @return string[]
     */
    private function getListmonkListSubscriberEmails(string $listId): array
    {
        $subscribers = $this->performListmonkRequest('subscribers', Request::METHOD_GET, [
            'list_id' => $listId,
            'per_page' => 'all',
        ]);

        if (isset($subscribers['data']['results'])) {
            return array_map(
                static fn (array $subscriber) => $subscriber['email'],
                $subscribers['data']['results'],
            );
        }

        return [];
    }
}