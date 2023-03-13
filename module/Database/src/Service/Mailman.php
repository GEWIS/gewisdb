<?php

declare(strict_types=1);

namespace Database\Service;

use Application\Model\Enums\ConfigNamespaces;
use Application\Service\Config as ConfigService;
use Database\Mapper\MailingListMember as MailingListMemberMapper;
use Database\Model\MailingListMember as MailingListMemberModel;
use DateTime;
use Laminas\Cache\Storage\Adapter\AbstractAdapter;
use Laminas\Http\Client;
use Laminas\Http\Client\Adapter\Curl;
use Laminas\Http\Request;
use RuntimeException;

use function array_column;
use function json_decode;
use function json_last_error_msg;
use function json_validate;

class Mailman
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function __construct(
        private readonly AbstractAdapter $mailmanCache,
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
        $request = new Request();

        $request->setMethod($method)
            ->setUri($this->mailmanConfig['endpoint'] . $uri);
        $client->setAdapter(Curl::class)
            ->setAuth($this->mailmanConfig['username'], $this->mailmanConfig['password']);

        // Data encoding is automatically set to `application/x-www-form-urlencoded` for "POST"-like requests.
        switch ($method) {
            case Request::METHOD_GET:
                $client->setParameterGet($data);
                break;
            case Request::METHOD_POST:
            case Request::METHOD_DELETE:
            case Request::METHOD_PATCH:
                $client->setParameterPost($data);
                break;
        }

        try {
            $response = $client->send($request);
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
    public function acquireSyncLock(int $retries = 3): void
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
    public function releaseSyncLock(): void
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
     * @return string[]
     */
    private function getAllListIdsFromMailman(): array
    {
        $lists = $this->performMailmanRequest('lists');

        if (
            isset($lists['total_size'])
            && 0 !== $lists['total_size']
        ) {
            return array_column($lists['entries'], 'list_id');
        }

        return [];
    }

    public function cacheMailingLists(): void
    {
        $this->mailmanCache->setItem(
            'lists',
            [
                'synced' => new DateTime(),
                'lists' => $this->getAllListIdsFromMailman(),
            ],
        );
    }

    /**
     * @return array{
     *     synced: DateTime,
     *     lists: string[],
     * }
     */
    public function getMailingListIds(): array
    {
        if (!$this->mailmanCache->hasItem('lists')) {
            $this->cacheMailingLists();
        }

        return $this->mailmanCache->getItem('lists');
    }

    /**
     * Subscribe a member to a mailing list.
     *
     * Unfortunately, this must be done one at the time as there is no mass-subscribe functionality in the API. See
     * https://gitlab.com/mailman/mailman/-/issues/419 for the open issue.
     */
    private function subscribeMemberToMailingList(MailingListMemberModel $mailingListMember): void
    {
        $member = $mailingListMember->getMember();
        $listId = $mailingListMember->getMailingList()->getMailmanId();

        // Create the data for the request
        $data = [
            'list_id' => $listId,
            'subscriber' => $member->getEmail(),
            'display_name' => $member->getFullName(),
            'role' => 'member',
            'pre_verified' => true,
            'pre_confirmed' => true,
            'pre_approved' => true,
            'send_welcome_message' => false,
            'delivery_mode' => 'regular',
            'delivery_status' => 'enabled',
        ];

        // Send the request to the Mailman API
        $mailingListMember->setLastSyncOn(new DateTime());
        $response = $this->performMailmanRequest(
            uri: 'members',
            method: Request::METHOD_POST,
            data: $data,
        );

        // Check if the request was successful
        if (isset($response['member_id'])) {
            $mailingListMember->setLastSyncSuccess(true);
            $mailingListMember->setMembershipId($response['member_id']);
        } else {
            $mailingListMember->setLastSyncSuccess(false);
        }

        $this->mailingListMemberMapper->persist($mailingListMember);
    }

    public function unsubscribeMemberFromMailingList(): void
    {
    }

    public function massUnsubscribeMembersFromMailingList(): void
    {
    }
}
