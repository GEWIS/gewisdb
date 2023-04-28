<?php

declare(strict_types=1);

namespace User\Service;

use User\Mapper\ApiPrincipalMapper;
use User\Model\ApiPrincipal as ApiPrincipalModel;

class ApiPrincipalService
{
    public function __construct(
        protected readonly ApiPrincipalMapper $mapper,
    ) {
    }

    /**
     * Get all Api Principals.
     *
     * @return ApiPrincipalModel[]
     */
    public function findAll(): array
    {
        return $this->mapper->findAll();
    }
}
