<?php

declare(strict_types=1);

namespace App\Repository\Database;

use App\Entity\Database\ActionLink;
use App\Entity\Database\Member;
use App\Entity\Database\PaymentLink;
use App\Entity\Database\RenewalLink;
use DateInterval;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActionLink>
 */
class ActionLinkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            ActionLink::class,
        );
    }

    public function findPaymentByToken(string $token): ?PaymentLink
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('pl, m')
            ->from(
                PaymentLink::class,
                'pl',
            )
            ->leftJoin(
                'pl.prospectiveMember',
                'm',
            )
            ->where('pl.token = :token');

        $qb->setParameter(
            ':token',
            $token,
        );

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findPaymentByProspectiveMember(int $lidnr): ?PaymentLink
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('pl')
            ->from(
                PaymentLink::class,
                'pl',
            )
            ->where('pl.prospectiveMember = :lidnr');

        $qb->setParameter(
            ':lidnr',
            $lidnr,
        );

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findRenewalByToken(string $token): ?RenewalLink
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('rl, m')
            ->from(
                RenewalLink::class,
                'rl',
            )
            ->leftJoin(
                'rl.member',
                'm',
            )
            ->where('rl.token = :token');

        $qb->setParameter(
            ':token',
            $token,
        );

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Get all renewal links for a member
     *
     * @return array<array-key, RenewalLink>|null
     */
    public function findRenewalByMember(int $lidnr): ?array
    {
        return $this->getEntityManager()->getRepository(RenewalLink::class)->findBy(['member' => $lidnr]);
    }

    /**
     * Create a renewal link for a member.
     *
     * If no expiration date is given, we renew until the first July 1st after the current expiration date +
     * at most an extra 31 days to prevent two renewals within one month.
     */
    public function createRenewalByMember(
        Member $member,
        ?DateTime $newExpiration = null,
    ): ?RenewalLink {
        if (null === $newExpiration) {
            $newExpiration = new DateTime();
            // Expire at midnight on July 1st, renewing at most 366 + 31 days
            $newExpiration->setTime(
                0,
                0,
            );
            $newExpiration->setDate(
                ((int) $member->getExpiration()->format('Y')) + 1,
                7,
                1,
            );

            while ($newExpiration->diff($member->getExpiration())->days > 397) {
                $newExpiration->sub(new DateInterval('P1Y'));
            }
        }

        $actionLink = new RenewalLink(
            $member,
            $newExpiration,
        );
        $this->persist($actionLink);

        return $actionLink;
    }

    public function remove(ActionLink $link): void
    {
        $this->getEntityManager()->remove($link);
        $this->getEntityManager()->flush();
    }

    public function persist(ActionLink $link): void
    {
        $this->getEntityManager()->persist($link);
        $this->getEntityManager()->flush();
    }
}
