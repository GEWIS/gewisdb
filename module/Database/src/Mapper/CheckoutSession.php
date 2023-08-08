<?php

declare(strict_types=1);

namespace Database\Mapper;

use Database\Model\CheckoutSession as CheckoutSessionModel;
use Database\Model\ProspectiveMember as ProspectiveMemberModel;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

class CheckoutSession
{
    public function __construct(protected readonly EntityManager $em)
    {
    }

    public function findById(string $id): ?CheckoutSessionModel
    {
        $qb = $this->getRepository()->createQueryBuilder('cs');
        $qb->where('cs.checkoutId = :id');

        $qb->setParameter('id', $id);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findLatest(ProspectiveMemberModel $prospectiveMember): ?CheckoutSessionModel
    {
        $qb = $this->getRepository()->createQueryBuilder('cs');
        $qb->where('cs.prospectiveMember = :prospectiveMember')
            ->setMaxResults(1)
            ->orderBy('cs.id', 'DESC');

        $qb->setParameter('prospectiveMember', $prospectiveMember);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findRecoveredBy(CheckoutSessionModel $checkoutSession): ?CheckoutSessionModel
    {
        $qb = $this->getRepository()->createQueryBuilder('cs');
        $qb->where('cs.recoveredFrom = :checkoutSession');

        $qb->setParameter('checkoutSession', $checkoutSession);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Persist a payment state.
     */
    public function persist(CheckoutSessionModel $payment): void
    {
        $this->em->persist($payment);
        $this->em->flush();
    }

    /**
     * Get the repository for this mapper.
     */
    public function getRepository(): EntityRepository
    {
        return $this->em->getRepository(CheckoutSessionModel::class);
    }
}
