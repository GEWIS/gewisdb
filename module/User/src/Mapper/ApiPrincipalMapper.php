<?php

declare(strict_types=1);

namespace User\Mapper;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use SensitiveParameter;
use User\Model\ApiPrincipal as ApiPrincipalModel;

use function count;

class ApiPrincipalMapper
{
    public function __construct(protected readonly EntityManager $em)
    {
    }

    /**
     * @return ApiPrincipalModel[]
     */
    public function findAll(): array
    {
        return $this->getRepository()->findAll();
    }

    public function find(int $id): ?ApiPrincipalModel
    {
        return $this->getRepository()->find($id);
    }

    public function findByToken(
        #[SensitiveParameter]
        string $token,
    ): ?ApiPrincipalModel {
        /** @var ApiPrincipalModel[] $results */
        $results = $this->getRepository()->findBy(['token' => $token], limit: 1);

        return count($results) > 0 ? $results[0] : null;
    }

    public function persist(ApiPrincipalModel $principal): void
    {
        $this->em->persist($principal);
        $this->em->flush();
    }

    public function remove(ApiPrincipalModel $principal): void
    {
        $this->em->remove($principal);
        $this->em->flush();
    }

    public function getRepository(): EntityRepository
    {
        return $this->em->getRepository(ApiPrincipalModel::class);
    }
}
