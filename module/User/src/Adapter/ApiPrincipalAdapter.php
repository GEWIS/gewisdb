<?php

declare(strict_types=1);

namespace User\Adapter;

use Laminas\Authentication\Adapter\AdapterInterface;
use Laminas\Authentication\Result;
use User\Mapper\ApiPrincipalMapper;

class ApiPrincipalAdapter implements AdapterInterface
{
    private string $token;

    public function __construct(private readonly ApiPrincipalMapper $mapper)
    {
    }

    /**
     * Try to authenticate.
     *
     * @return Result
     */
    public function authenticate(): Result
    {
        $principal = $this->mapper->findByToken($this->token);

        if (null === $principal) {
            return new Result(
                Result::FAILURE_IDENTITY_NOT_FOUND,
                null,
            );
        }

        return new Result(Result::SUCCESS, $principal);
    }

    /**
     * Sets the credentials used to authenticate.
     *
     * @param string $token
     */
    public function setCredentials(string $token): void
    {
        $this->token = $token;
    }

    /**
     * Get the mapper.
     *
     * @return ApiPrincipalMapper
     */
    public function getMapper(): ApiPrincipalMapper
    {
        return $this->mapper;
    }
}
