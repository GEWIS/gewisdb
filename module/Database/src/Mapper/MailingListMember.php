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
