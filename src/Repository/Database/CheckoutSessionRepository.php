<?php

declare(strict_types=1);

namespace App\Repository\Database;

use App\Entity\Database\CheckoutSession;
use App\Entity\Database\ProspectiveMember;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CheckoutSession>
 */
class CheckoutSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            CheckoutSession::class,
        );
    }

    public function findById(string $id): ?CheckoutSession
    {
        $qb = $this->createQueryBuilder('cs');
        $qb->where('cs.checkoutId = :id');

        $qb->setParameter(
            'id',
            $id,
        );

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findLatest(ProspectiveMember $prospectiveMember): ?CheckoutSession
    {
        $qb = $this->createQueryBuilder('cs');
        $qb->where('cs.prospectiveMember = :prospectiveMember')
            ->setMaxResults(1)
            ->orderBy(
                'cs.id',
                'DESC',
            );

        $qb->setParameter(
            'prospectiveMember',
            $prospectiveMember,
        );

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Persist a payment state.
     */
    public function persist(CheckoutSession $payment): void
    {
        $this->getEntityManager()->persist($payment);
        $this->getEntityManager()->flush();
    }
}
