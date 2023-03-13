<?php

declare(strict_types=1);

namespace Database\Service;

use DateTime;
use Laminas\Cache\Storage\Adapter\AbstractAdapter;
use Laminas\Http\Client;
use Laminas\Http\Client\Adapter\Curl;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Json\Json;
use RuntimeException;

use function array_column;

class Mailman
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function __construct(
        private readonly AbstractAdapter $mailmanCache,
        private readonly array $mailmanConfig,
    ) {
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    private function createMailmanRequest(
        string $uri,
        string $method = Request::METHOD_GET,
        string $encoding = 'application/json',
        array $data = [],
    ): Response|false {
        $client = new Client();
        $request = new Request();

        $request->setMethod($method)
            ->setUri($this->mailmanConfig['endpoint'] . $uri);
        $client->setAdapter(Curl::class)
            ->setAuth($this->mailmanConfig['username'], $this->mailmanConfig['password'])
            ->setEncType($encoding);

        switch ($method) {
            case Request::METHOD_GET:
                $client->setParameterGet($data);
                break;
            case Request::METHOD_POST:
                $client->setParameterPost($data);
                break;
        }

        try {
            return $client->send($request);
        } catch (RuntimeException) {
            return false;
        }
    }

    /**
     * @return string[]
     */
    private function getAllListIdsFromMailman(): array
    {
        if (false !== ($response = $this->createMailmanRequest('lists'))) {
            if (200 === $response->getStatusCode()) {
                $lists = Json::decode($response->getBody(), Json::TYPE_ARRAY);

                if (0 !== $lists['total_size']) {
                    return array_column($lists['entries'], 'list_id');
                }
            }
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
}
