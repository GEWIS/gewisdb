<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\User\ApiPrincipal;
use App\Repository\User\ApiPrincipalRepository;

final readonly class ApiPrincipalService
{
    public function __construct(private ApiPrincipalRepository $apiPrincipalRepository)
    {
    }

    /**
     * @return ApiPrincipal[]
     */
    public function findAll(): array
    {
        return $this->apiPrincipalRepository->findAll();
    }

    public function find(int $id): ?ApiPrincipal
    {
        return $this->apiPrincipalRepository->find($id);
    }

    /**
     * The token is minted here and never taken from input; it can be read back in full exactly once, right after
     * this call.
     */
    public function create(ApiPrincipal $principal): void
    {
        $principal->generateToken();

        $this->apiPrincipalRepository->persist($principal);
    }

    public function save(ApiPrincipal $principal): void
    {
        $this->apiPrincipalRepository->persist($principal);
    }

    public function remove(ApiPrincipal $principal): void
    {
        $this->apiPrincipalRepository->remove($principal);
    }
}
