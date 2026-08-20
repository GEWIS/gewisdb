<?php

declare(strict_types=1);

namespace App\Repository\Database;

use App\Entity\Database\MailingList;
use App\Entity\Database\MailingListMember;
use App\Entity\Database\Member;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MailingListMember>
 */
class MailingListMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            MailingListMember::class,
        );
    }

    /**
     * Persist a membership.
     */
    public function persist(MailingListMember $list): void
    {
        $this->getEntityManager()->persist($list);
        $this->getEntityManager()->flush();
    }

    /**
     * Remove a membership.
     */
    public function remove(MailingListMember $list): void
    {
        $this->getEntityManager()->remove($list);
        $this->getEntityManager()->flush();
    }

    public function findByListAndMember(
        MailingList $list,
        Member $member,
    ): ?MailingListMember {
        $qb = $this->createQueryBuilder('m');
        $qb->where('m.mailingList = :list')
            ->andWhere('m.member = :member');

        $qb->setParameter(
            'list',
            $list,
        )
            ->setParameter(
                'member',
                $member,
            );

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Get the pending number of creations
     * Intentionally, does not do a findAll
     */
    public function countPendingCreation(): int
    {
        $qb = $this->createQueryBuilder('mlm');

        $qb->select($qb->expr()->count('mlm.member'))
            ->where('mlm.toBeCreated = True');

        $query = $qb->getQuery();

        return (int) $query->getSingleScalarResult();
    }

    /**
     * Get the pending number of deletions
     * Intentionally, does not do a findAll
     */
    public function countPendingDeletion(): int
    {
        $qb = $this->createQueryBuilder('mlm');

        $qb->select($qb->expr()->count('mlm.member'))
            ->where('mlm.toBeDeleted = True');

        $query = $qb->getQuery();

        return (int) $query->getSingleScalarResult();
    }

    /**
     * Mark all members of a mailing list as needing to be created.
     * This does not perform changes in report which is correct.
     */
    public function markAllMembersForCreation(MailingList $mailingList): void
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->update(
            MailingListMember::class,
            'mlm',
        )
            ->set(
                'mlm.toBeCreated',
                'true',
            )
            ->where('mlm.mailingList = :list')
            ->andWhere('mlm.toBeDeleted != true')
            ->setParameter(
                'list',
                $mailingList,
            );

        $qb->getQuery()->execute();
    }

    /**
     * Get the mailing list members that belong to hidden or expired members
     * and that are not already scheduled for deletion
     *
     * Note (for testing) that we also check for active renewal links, but that is done
     * in the service layer.
     *
     * @return MailingListMember[]
     */
    public function findAllExpiredOrHidden(): array
    {
        $qb = $this->createQueryBuilder('mlm');

        $qb->leftJoin(
            'mlm.member',
            'm',
        )
            ->where( //X
                $qb->expr()->notIn(
                    'mlm.member',
                    MemberRepository::getMembershipSubquery(
                        $qb,
                        includeGraduates: true,
                        includeFutureMembers: false,
                    )->getDQL(),
                ),
            )
            ->orWhere('m.hidden = True') //Y
            ->andWhere('mlm.toBeDeleted != True'); //Z
        //this results in (X OR Y) AND Z, which is what we want

        /** @var MailingListMember[] $result */
        $result = $qb->getQuery()->getResult();

        return $result;
    }

    /**
     * Get memberships with pending sync flags for lists without external backends.
     *
     * @return MailingListMember[]
     */
    public function findAllPendingLocalOnly(): array
    {
        $qb = $this->createQueryBuilder('mlm');

        $qb->innerJoin(
            'mlm.mailingList',
            'ml',
        )
            ->where('ml.mailmanList IS NULL')
            ->andWhere('ml.listmonkList IS NULL')
            ->andWhere('mlm.toBeCreated = True OR mlm.toBeDeleted = True');

        /** @var MailingListMember[] $result */
        $result = $qb->getQuery()->getResult();

        return $result;
    }
}
