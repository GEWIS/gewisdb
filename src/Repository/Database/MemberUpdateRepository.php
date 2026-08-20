<?php

declare(strict_types=1);

namespace App\Repository\Database;

use App\Entity\Database\MemberUpdate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MemberUpdate>
 */
class MemberUpdateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            MemberUpdate::class,
        );
    }

    /**
     * @return MemberUpdate[]
     */
    public function getPendingUpdates(): array
    {
        return $this->findAll();
    }

    public function persist(MemberUpdate $memberUpdate): void
    {
        $this->getEntityManager()->persist($memberUpdate);
        $this->getEntityManager()->flush();
    }

    public function remove(MemberUpdate $memberUpdate): void
    {
        $this->getEntityManager()->remove($memberUpdate);
        $this->getEntityManager()->flush();
    }
}
