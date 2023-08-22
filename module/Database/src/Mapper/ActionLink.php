<?php

declare(strict_types=1);

namespace Database\Mapper;

use Database\Model\ActionLink as ActionLinkModel;
use Database\Model\Member as MemberModel;
use Database\Model\PaymentLink as PaymentLinkModel;
use Database\Model\RenewalLink as RenewalLinkModel;
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
     * Find an payment link by token.
     */
    public function findPaymentByToken(string $token): ?PaymentLinkModel
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('pl, m')
            ->from(PaymentLinkModel::class, 'pl')
            ->leftJoin('pl.prospectiveMember', 'm')
            ->where('pl.token = :token');

        $qb->setParameter(':token', $token);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findPaymentByProspectiveMember(int $lidnr): ?PaymentLinkModel
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('pl')
            ->from(PaymentLinkModel::class, 'pl')
            ->where('pl.prospectiveMember = :lidnr');

        $qb->setParameter(':lidnr', $lidnr);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Find an renewal link by token.
     */
    public function findRenewalByToken(string $token): ?RenewalLinkModel
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('rl, m')
            ->from(RenewalLinkModel::class, 'rl')
            ->leftJoin('rl.member', 'm')
            ->where('rl.token = :token');

        $qb->setParameter(':token', $token);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Get all renewal links for a member
     *
     * @return array<array-key, RenewalLinkModel>
     */
    public function findRenewalByMember(int $lidnr): ?array
    {
        return $this->getRenewalRepository()->findBy(['member' => $lidnr]);
    }

    /**
     * Create a renewal link for a member.
     *
     * If no expiration date is given, we renew until the first July 1st after the current expiration date +
     * at most an extra 31 days to prevent two renewals within one month.
     */
    public function createRenewalByMember(
        MemberModel $member,
        ?DateTime $newExpiration = null,
    ): ?RenewalLinkModel {
        if (null === $newExpiration) {
            $newExpiration = new DateTime();
            // Expire at midnight on July 1st, renewing at most 366 + 31 days
            $newExpiration->setTime(0, 0);
            $newExpiration->setDate(((int) $member->getExpiration()->format('Y')) + 1, 7, 1);

            while ($newExpiration->diff($member->getExpiration())->days > 397) {
                $newExpiration->sub(new DateInterval('P1Y'));
            }
        }

        $actionLink = new RenewalLinkModel($member, $newExpiration);
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
     * Get the repository for renewal links.
     */
    private function getRenewalRepository(): EntityRepository
    {
        return $this->em->getRepository(RenewalLinkModel::class);
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
