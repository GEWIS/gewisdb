<?php

namespace Api\Service;

use Api\Mapper\ApiKey as ApiKeyMapper;
use Api\Model\ApiKey as ApiKeyModel;

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
}
