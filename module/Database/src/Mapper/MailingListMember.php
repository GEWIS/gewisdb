<?php

declare(strict_types=1);

namespace Database\Mapper;

use Database\Model\MailingList as MailingListModel;
use Database\Model\MailingListMember as MailingListMemberModel;
use Database\Model\Member as MemberModel;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

/**
 * Mailing list member mapper.
 */
class MailingListMember
{
    public function __construct(protected readonly EntityManager $em)
    {
    }

    /**
     * Persist a membership.
     */
    public function persist(MailingListMemberModel $list): void
    {
        $this->em->persist($list);
        $this->em->flush();
    }

    /**
     * Remove a membership.
     */
    public function remove(MailingListMemberModel $list): void
    {
        $this->em->remove($list);
        $this->em->flush();
    }

    public function findByListAndMember(
        MailingListModel $list,
        MemberModel $member,
    ): ?MailingListMemberModel {
        $qb = $this->getRepository()->createQueryBuilder('m');
        $qb->where('m.mailingList = :list')
            ->andWhere('m.member = :member');

        $qb->setParameter('list', $list)
            ->setParameter('member', $member);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Get the pending number of creations
     * Intentionally, does not do a findAll
     */
    public function countPendingCreation(): int
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select($qb->expr()->count('mlm.member'))
            ->from(MailingListMemberModel::class, 'mlm')
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
        $qb = $this->em->createQueryBuilder();

        $qb->select($qb->expr()->count('mlm.member'))
            ->from(MailingListMemberModel::class, 'mlm')
            ->where('mlm.toBeDeleted = True');

        $query = $qb->getQuery();

        return (int) $query->getSingleScalarResult();
    }

    /**
     * Get the mailing list members that should exist after the next sync
     * Value of toBeCreated does not matter, toBeDeleted should be excluded
     *
     * @return MailingListMemberModel[]
     */
    public function findAfterSync(): array
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('mlm')
            ->from(MailingListMemberModel::class, 'mlm')
            ->where('mlm.toBeDeleted != True');

        /** @var MailingListMemberModel[] $result */
        $result = $qb->getQuery()->getResult();

        return $result;
    }

    /**
     * Get the mailing list members that belong to hidden or expired members
     * and that are not already scheduled for deletion
     *
     * @return MailingListMemberModel[]
     */
    public function findAllExpiredOrHidden(): array
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('mlm')
            ->from(MailingListMemberModel::class, 'mlm')
            ->leftJoin('mlm.member', 'm')
            ->where('mlm.toBeDeleted != True')
            ->andWhere('m.expiration <= CURRENT_TIMESTAMP() OR m.hidden = True');

        /** @var MailingListMemberModel[] $result */
        $result = $qb->getQuery()->getResult();

        return $result;
    }

    /**
     * @return MailingListMemberModel[]
     */
    public function findAll(): array
    {
        return $this->getRepository()->findAll();
    }

    /**
     * Get the repository for this mapper.
     */
    public function getRepository(): EntityRepository
    {
        return $this->em->getRepository(MailingListMemberModel::class);
    }
}
