<?php

declare(strict_types=1);

namespace Database\Mapper;

use Database\Model\ActionLink as ActionLinkModel;
use Database\Model\Member as MemberModel;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

class ActionLink
{
    public function __construct(protected readonly EntityManager $em)
    {
    }

    /**
     * Find an action link by token
     */
    public function findByToken(string $token): ?ActionLinkModel
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('al, m')
            ->from(ActionLinkModel::class, 'al')
            ->leftJoin('al.member', 'm')
            ->where('al.token = :token');

        $qb->setParameter(':token', $token);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Create an action link for a member
     * If no expiration date is given, we renew until the first July 1st after the current expiration date +
     * at most an extra 31 days to prevent two renewals within one month
     */
    public function createByMember(
        MemberModel $member,
        ?DateTime $newExpiration = null,
    ): ?ActionLinkModel {
        if (null === $newExpiration) {
            $newExpiration = new DateTime();
            // Expire at midnight on July 1st, renewing at most 366 + 31 days
            $newExpiration->setTime(0, 0);
            $newExpiration->setDate(((int) $member->getExpiration()->format('Y')) + 1, 7, 1);
            while ($newExpiration->diff($member->getExpiration())->days > 397) {
                $newExpiration->sub(new DateInterval('P1Y'));
            }
        }

        $actionLink = new ActionLinkModel($member, $newExpiration);
        $this->persist($actionLink);

        return $actionLink;
    }

    /**
     * Get the repository for this mapper.
     */
    public function getRepository(): EntityRepository
    {
        return $this->em->getRepository(ActionLinkModel::class);
    }

    /**
     * Delete an action link.
     */
    public function remove(ActionLinkModel $link): void
    {
        $this->em->remove($link);
        $this->em->flush();
    }

    /**
     * Persist an action link.
     */
    public function persist(ActionLinkModel $link): void
    {
        $this->em->persist($link);
        $this->em->flush();
    }
}
