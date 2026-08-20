<?php

declare(strict_types=1);

namespace App\Repository\Database;

use App\Entity\Database\CheckoutSession;
use App\Entity\Database\Enums\CheckoutSessionStates;
use App\Entity\Database\Enums\ProspectiveMemberFilter;
use App\Entity\Database\ProspectiveMember;
use DateInterval;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join as JoinExpr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

use function addcslashes;
use function is_numeric;
use function mb_strtolower;
use function str_replace;
use function strtolower;
use function trim;

/**
 * @extends ServiceEntityRepository<ProspectiveMember>
 */
class ProspectiveMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            ProspectiveMember::class,
        );
    }

    /**
     * See if we can find a member with the same email.
     */
    public function hasMemberWith(string $email): bool
    {
        $qb = $this->createQueryBuilder('m');

        $qb->where('LOWER(m.email) = LOWER(:email)')
            ->setMaxResults(1);

        $qb->setParameter(
            ':email',
            $email,
        );

        $ret = $qb->getQuery()->getResult();

        return [] !== $ret;
    }

    /**
     * Search for a member.
     *
     * @return array<array-key, ProspectiveMember>
     */
    public function search(
        string $query,
        string $type,
    ): array {
        $qb = $this->createQueryBuilder('m');

        $qb->where("CONCAT(LOWER(m.firstName), ' ', LOWER(m.lastName)) LIKE :name")
            ->orWhere("CONCAT(LOWER(m.firstName), ' ', LOWER(m.middleName), ' ', LOWER(m.lastName)) LIKE :name")
            ->orWhere('m.email LIKE :name')
            ->setMaxResults(128)
            ->orderBy(
                'm.lidnr',
                'DESC',
            )
            ->setFirstResult(0);

        $qb->setParameter(
            ':name',
            '%' . strtolower($query) . '%',
        );

        // also allow searching for membership number
        if (is_numeric($query)) {
            $qb->orWhere('m.lidnr = :nr');
            $qb->orWhere('m.studentNumber = :nr');
            $qb->setParameter(
                ':nr',
                $query,
            );
        }

        // Get Checkout Session status.
        $qb->leftJoin(
            CheckoutSession::class,
            'cs',
            JoinExpr::WITH,
            'cs.prospectiveMember = m.lidnr',
        );
        $qbc = $this->getEntityManager()->createQueryBuilder();
        $qbc->select('MAX(css.id)')
            ->from(
                CheckoutSession::class,
                'css',
            )
            ->where('css.prospectiveMember = m.lidnr');
        $qb->andWhere($qb->expr()->orX(
            $qb->expr()->eq(
                'cs.id',
                '(' . $qbc->getDQL() . ')',
            ),
            $qb->expr()->isNull('cs.id'),
        ));

        if ('paid' === $type) {
            $qb->andWhere('cs.state = :paid')
                ->setParameter(
                    'paid',
                    CheckoutSessionStates::Paid,
                );
        } elseif ('failed' === $type) {
            $qb->andWhere('cs.state = :expired OR cs.state = :failed OR cs.state IS NULL')
                ->setParameter(
                    'expired',
                    CheckoutSessionStates::Expired,
                )
                ->setParameter(
                    'failed',
                    CheckoutSessionStates::Failed,
                );
        } else {
            $qb->andWhere('cs.state = :created OR cs.state = :pending')
                ->setParameter(
                    'created',
                    CheckoutSessionStates::Created,
                )
                ->setParameter(
                    'pending',
                    CheckoutSessionStates::Pending,
                );
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all prospective members whose last Checkout Session has fully expired ((1/24 + 30) + 1 day ago) or failed 31
     * days ago.
     *
     * @return ProspectiveMember[]
     */
    public function findWithFullyExpiredOrFailedCheckout(): array
    {
        // Get all prospective members and their checkout sessions
        $qb = $this->createQueryBuilder('m');
        $qb->leftJoin(
            CheckoutSession::class,
            'cs',
            JoinExpr::WITH,
            'cs.prospectiveMember = m.lidnr',
        );

        // Subquery to get maximum checkout session for a member
        $qbc = $this->getEntityManager()->createQueryBuilder();
        $qbc->select('MAX(csm.id)')
            ->from(
                CheckoutSession::class,
                'csm',
            )
            ->where('csm.prospectiveMember = m.lidnr');

        // Subquery to get the original (expired) checkout session (the one that could be recovered)
        $qbd = $this->getEntityManager()->createQueryBuilder();
        $qbd->select('(CASE WHEN css.recoveredFrom IS NOT NULL THEN IDENTITY(css.recoveredFrom) ELSE css.id END)')
            ->from(
                CheckoutSession::class,
                'css',
            )
            ->where('css.prospectiveMember = m.lidnr')
            ->andWhere($qb->expr()->eq('css.id', '(' . str_replace('csm', 'csm2', $qbc->getDQL()) . ')'))
            ->andWhere('css.state = :expired');

        $qb->where($qb->expr()->orX(
            // Get the last checkout session, if it has failed more than 31 days ago
            $qb->expr()->andX(
                $qb->expr()->eq(
                    'cs.id',
                    '(' . $qbc->getDQL() . ')',
                ),
                $qb->expr()->eq(
                    'cs.state',
                    ':failed',
                ),
                $qb->expr()->lt(
                    'cs.expiration',
                    ':fullyFailed',
                ),
            ),
            // OR get the original session if it has expired more than a day ago using that
            // if x.state == Expired, the expiration date is the last date the checkout session can be recoverd
            $qb->expr()->andX(
                $qb->expr()->eq(
                    'cs.id',
                    '(' . $qbd->getDQL() . ')',
                ),
                $qb->expr()->eq(
                    'cs.state',
                    ':expired',
                ),
                $qb->expr()->lt(
                    'cs.expiration',
                    ':fullyExpired',
                ),
            ),
        ));

        $qb->setParameter(
            'expired',
            CheckoutSessionStates::Expired,
        )
            ->setParameter(
                'failed',
                CheckoutSessionStates::Failed,
            )
            ->setParameter(
                'fullyExpired',
                new DateTime()->sub(new DateInterval('P1D')),
            )
            ->setParameter(
                'fullyFailed',
                new DateTime()->sub(new DateInterval('P31D')),
            );

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all prospective members wihout any checkout session (should not happen)
     *
     * @return ProspectiveMember[]
     */
    public function findWithoutCheckout(): array
    {
        // Get all checkout sessions
        $checkoutSessions = $this->getEntityManager()->createQueryBuilder();
        $checkoutSessions->select('pmwithcs.lidnr')
            ->from(
                CheckoutSession::class,
                'cs',
            )
            ->innerJoin(
                'cs.prospectiveMember',
                'pmwithcs',
            );

        // Get all prospective members without a checkout session that are there for more than 30 days
        $qb = $this->createQueryBuilder('m');
        $qb->where($qb->expr()->notIn('m.lidnr', $checkoutSessions->getDQL()))
            ->andWhere('m.changedOn <= :fullyExpired');

        $qb->setParameter(
            'fullyExpired',
            new DateTime()->sub(new DateInterval('P31D')),
        );

        return $qb->getQuery()->getResult();
    }

    /**
     * One page of the prospective-member overview.
     *
     * @return Paginator<ProspectiveMember>
     */
    public function paginateForOverview(
        string $search,
        ProspectiveMemberFilter $filter,
        int $page,
        int $pageSize,
    ): Paginator {
        $qb = $this->createQueryBuilder('m')
            ->orderBy(
                'm.lidnr',
                'DESC',
            )
            ->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize);

        $this->applyLatestCheckout($qb);
        $this->applyOverviewFilter(
            $qb,
            $filter,
        );
        $this->applyOverviewSearch(
            $qb,
            $search,
        );

        return new Paginator(
            $qb->getQuery(),
            false,
        );
    }

    /**
     * How many applications each filter would return, so the chips can say so before they are clicked.
     *
     * @return array<string, int>
     */
    public function countsForOverview(string $search): array
    {
        $counts = [];

        foreach (ProspectiveMemberFilter::cases() as $filter) {
            $qb = $this->createQueryBuilder('m')->select('COUNT(DISTINCT m.lidnr)');

            $this->applyLatestCheckout($qb);
            $this->applyOverviewFilter(
                $qb,
                $filter,
            );
            $this->applyOverviewSearch(
                $qb,
                $search,
            );

            $counts[$filter->value] = (int) $qb->getQuery()->getSingleScalarResult();
        }

        return $counts;
    }

    /**
     * How many applicants a filter would return.
     *
     * Separate from {@see self::countsForOverview()}, which answers for every filter at once: the dashboard wants one
     * of them and would otherwise pay for four queries to read a single number.
     */
    public function countForFilter(ProspectiveMemberFilter $filter): int
    {
        $qb = $this->createQueryBuilder('m')->select('COUNT(DISTINCT m.lidnr)');

        $this->applyLatestCheckout($qb);
        $this->applyOverviewFilter(
            $qb,
            $filter,
        );

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Join only the applicant's most recent checkout session, since an applicant who restarted a checkout has several
     * and only the last one says where they are.
     */
    private function applyLatestCheckout(QueryBuilder $qb): void
    {
        $latest = $this->getEntityManager()->createQueryBuilder()
            ->select('MAX(css.id)')
            ->from(
                CheckoutSession::class,
                'css',
            )
            ->where('css.prospectiveMember = m.lidnr');

        $qb->leftJoin(
            CheckoutSession::class,
            'cs',
            JoinExpr::WITH,
            'cs.prospectiveMember = m.lidnr',
        )
            ->andWhere($qb->expr()->orX(
                $qb->expr()->eq(
                    'cs.id',
                    '(' . $latest->getDQL() . ')',
                ),
                $qb->expr()->isNull('cs.id'),
            ));
    }

    private function applyOverviewFilter(
        QueryBuilder $qb,
        ProspectiveMemberFilter $filter,
    ): void {
        match ($filter) {
            ProspectiveMemberFilter::All => null,
            ProspectiveMemberFilter::Paid => $qb->andWhere('cs.state = :paid')
                ->setParameter(
                    'paid',
                    CheckoutSessionStates::Paid,
                ),
            ProspectiveMemberFilter::AwaitingPayment => $qb->andWhere('cs.state IN (:awaiting)')
                ->setParameter(
                    'awaiting',
                    [
                        CheckoutSessionStates::Created,
                        CheckoutSessionStates::Pending,
                    ],
                ),
            // No session at all counts as failed: the applicant exists and there is nothing to pay against.
            ProspectiveMemberFilter::ExpiredOrFailed => $qb->andWhere(
                $qb->expr()->orX(
                    'cs.state IN (:over)',
                    'cs.state IS NULL',
                ),
            )->setParameter(
                'over',
                [
                    CheckoutSessionStates::Expired,
                    CheckoutSessionStates::Failed,
                ],
            ),
        };
    }

    private function applyOverviewSearch(
        QueryBuilder $qb,
        string $search,
    ): void {
        $search = trim($search);

        if ('' === $search) {
            return;
        }

        $matches = $qb->expr()->orX(
            "CONCAT(LOWER(m.firstName), ' ', LOWER(m.lastName)) LIKE :needle",
            "CONCAT(LOWER(m.firstName), ' ', LOWER(m.middleName), ' ', LOWER(m.lastName)) LIKE :needle",
            'LOWER(m.email) LIKE :needle',
            'm.studentNumber LIKE :needle',
        );

        if (is_numeric($search)) {
            $matches->add('m.lidnr = :lidnr');
            $qb->setParameter(
                'lidnr',
                (int) $search,
            );
        }

        $qb->andWhere($matches)
            ->setParameter(
                'needle',
                '%' . mb_strtolower(addcslashes($search, '%_')) . '%',
            );
    }

    public function persist(ProspectiveMember $member): void
    {
        $this->getEntityManager()->persist($member);
        $this->getEntityManager()->flush();
    }

    /**
     * Remove a member.
     */
    public function remove(ProspectiveMember $member): void
    {
        $this->getEntityManager()->remove($member);
        $this->getEntityManager()->flush();
    }
}
