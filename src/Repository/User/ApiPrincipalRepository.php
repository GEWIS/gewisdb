<?php

declare(strict_types=1);

namespace App\Repository\User;

use App\Entity\User\ApiPrincipal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use SensitiveParameter;

use function count;

/**
 * @extends ServiceEntityRepository<ApiPrincipal>
 */
class ApiPrincipalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            ApiPrincipal::class,
        );
    }

    public function findByToken(
        #[SensitiveParameter]
        string $token,
    ): ?ApiPrincipal {
        /** @var ApiPrincipal[] $results */
        $results = $this->findBy(
            ['token' => $token],
            limit: 1,
        );

        return count($results) > 0
            ? $results[0]
            : null;
    }

    public function persist(ApiPrincipal $principal): void
    {
        $this->getEntityManager()->persist($principal);
        $this->getEntityManager()->flush();
    }

    public function remove(ApiPrincipal $principal): void
    {
        $this->getEntityManager()->remove($principal);
        $this->getEntityManager()->flush();
    }
}
