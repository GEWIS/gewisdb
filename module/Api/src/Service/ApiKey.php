<?php

namespace Api\Service;

use Api\Mapper\ApiKey as ApiKeyMapper;
use Api\Model\ApiKey as ApiKeyModel;
use Zend\Math\Rand;

class ApiKey
{

    /**
     * @var ApiKeyMapper
     */
    protected $mapper;

    /**
     * Constructor.
     *
     * @param ApiKeyMapper $mapper
     */
    public function __construct(ApiKeyMapper $mapper)
    {
        $this->mapper = $mapper;
    }

    /**
     * Get all.
     * @return ApiKeyModel[]
     */
    public function findAll()
    {
        return $this->mapper->findAll();
    }

    /**
     * Create an API key.
     * @param string $name
     * @param string $webhook
     */
    public function create($name, $webhook)
    {
        $key = new ApiKeyModel();
        $key->setName($name);
        $key->setSecret(Rand::getString(42));
        $key->setWebhook($webhook);

        $this->mapper->persist($key);
    }
}
