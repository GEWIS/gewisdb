<?php

declare(strict_types=1);

namespace App\Repository\Database\SubDecision;

use App\Entity\Database\Enums\InstallationFunctions;
use App\Entity\Database\Enums\MeetingTypes;
use App\Entity\Database\SubDecision\Abrogation;
use App\Entity\Database\SubDecision\Annulment;
use App\Entity\Database\SubDecision\Discharge;
use App\Entity\Database\SubDecision\Foundation;
use App\Entity\Database\SubDecision\Installation;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

use function strtolower;

/**
 * Organs are not an entity of their own: an organ is the Foundation subdecision that created it, optionally undone by
 * an Abrogation. This repository is therefore over Foundation.
 *
 * @extends ServiceEntityRepository<Foundation>
 */
class FoundationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(
            $registry,
            Foundation::class,
        );
    }

    /**
     * Find an organ. Also calculate which are its current members.
     *
     * Not named find(), because EntityRepository::find() takes an identifier and cannot be narrowed.
     */
    public function findOrgan(
        MeetingTypes $meetingType,
        int $meetingNumber,
        int $decisionPoint,
        int $decisionNumber,
        int $sequence,
    ): ?Foundation {
        $qb = $this->createQueryBuilder('o');

        $qb->addSelect('r')
            ->where('o.meeting_type = :type')
            ->andWhere('o.meeting_number = :meeting_number')
            ->andWhere('o.decision_point = :decision_point')
            ->andWhere('o.decision_number = :decision_number')
            ->andWhere('o.sequence = :sequence')
            ->leftJoin(
                'o.references',
                'r',
            )
            ->andWhere('r INSTANCE OF ' . Installation::class);

        // discharges
        $qbn = $this->getEntityManager()->createQueryBuilder();
        $qbn->select('d')
            ->from(
                Discharge::class,
                'd',
            )
            ->join(
                'd.installation',
                'x',
            )
            ->where('x.meeting_type = r.meeting_type')
            ->andWhere('x.meeting_number = r.meeting_number')
            ->andWhere('x.decision_point = r.decision_point')
            ->andWhere('x.decision_number = r.decision_number')
            ->andWhere('x.sequence = r.sequence');

        // annulled discharge decisions
        $qbnd = $this->getEntityManager()->createQueryBuilder();
        $qbnd->select('b')
            ->from(
                Annulment::class,
                'b',
            )
            ->join(
                'b.target',
                'z',
            )
            ->where('z.meeting_type = d.meeting_type')
            ->andWhere('z.meeting_number = d.meeting_number')
            ->andWhere('z.point = d.decision_point')
            ->andWhere('z.number = d.decision_number');

        $qbn->andWhere($qbn->expr()->not(
            $qbn->expr()->exists($qbnd->getDQL()),
        ));

        $qb->andWhere($qb->expr()->not(
            $qb->expr()->exists($qbn->getDQL()),
        ));

        // annulled installation decisions
        $qbd = $this->getEntityManager()->createQueryBuilder();
        $qbd->select('a')
            ->from(
                Annulment::class,
                'a',
            )
            ->join(
                'a.target',
                'y',
            )
            ->where('y.meeting_type = r.meeting_type')
            ->andWhere('y.meeting_number = r.meeting_number')
            ->andWhere('y.point = r.decision_point')
            ->andWhere('y.number = r.decision_number');

        $qb->andWhere($qb->expr()->not(
            $qb->expr()->exists($qbd->getDQL()),
        ));

        $qb->setParameter(
            ':type',
            $meetingType,
        );
        $qb->setParameter(
            ':meeting_number',
            $meetingNumber,
        );
        $qb->setParameter(
            ':decision_point',
            $decisionPoint,
        );
        $qb->setParameter(
            ':decision_number',
            $decisionNumber,
        );
        $qb->setParameter(
            ':sequence',
            $sequence,
        );

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findSimple(
        MeetingTypes $meetingType,
        int $meetingNumber,
        int $decisionPoint,
        int $decisionNumber,
        ?int $sequence = null,
    ): ?Foundation {
        $qb = $this->createQueryBuilder('f');

        $qb->where('f.meeting_type = :meeting_type')
            ->andWhere('f.meeting_number = :meeting_number')
            ->andWhere('f.decision_point = :decision_point')
            ->andWhere('f.decision_number = :decision_number')
            ->andWhere('f.sequence = :sequence');

        $qb->setParameter(
            ':meeting_type',
            $meetingType,
        );
        $qb->setParameter(
            ':meeting_number',
            $meetingNumber,
        );
        $qb->setParameter(
            ':decision_point',
            $decisionPoint,
        );
        $qb->setParameter(
            ':decision_number',
            $decisionNumber,
        );
        $qb->setParameter(
            ':sequence',
            $sequence,
        );

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findInstallationDecision(
        MeetingTypes $meetingType,
        int $meetingNumber,
        int $decisionPoint,
        int $decisionNumber,
        ?int $sequence = null,
    ): ?Installation {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('i')
            ->from(
                Installation::class,
                'i',
            )
            ->where('i.meeting_type = :meeting_type')
            ->andWhere('i.meeting_number = :meeting_number')
            ->andWhere('i.decision_point = :decision_point')
            ->andWhere('i.decision_number = :decision_number')
            ->andWhere('i.sequence = :sequence');

        $qb->setParameter(
            ':meeting_type',
            $meetingType,
        );
        $qb->setParameter(
            ':meeting_number',
            $meetingNumber,
        );
        $qb->setParameter(
            ':decision_point',
            $decisionPoint,
        );
        $qb->setParameter(
            ':decision_number',
            $decisionNumber,
        );
        $qb->setParameter(
            ':sequence',
            $sequence,
        );

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Search for organ decisions.
     *
     * This is a really complicated query, we might want to create a
     * materialized view for organs, with a field if they are abrogated or not.
     *
     * And since events are implemented anyways, we might want to use that to
     * automatically process changes.
     *
     * @return Foundation[]
     */
    public function organSearch(
        string $query,
        bool $includeAbrogated = false,
    ): array {
        return $this->overviewQuery(
            $query,
            $includeAbrogated,
        )->getQuery()->getResult();
    }

    /**
     * One page of bodies, ordered by abbreviation.
     *
     * @return Paginator<Foundation>
     */
    public function paginateForOverview(
        string $search,
        int $page,
        int $pageSize,
    ): Paginator {
        $qb = $this->overviewQuery($search)
            ->orderBy(
                'o.abbr',
                'ASC',
            )
            // Abbreviations repeat, so on their own they leave the order of a page up to the database. The identity of
            // the founding decision settles the rest, so that paging through the list cannot show one body twice and
            // skip another.
            ->addOrderBy(
                'o.meeting_type',
                'ASC',
            )
            ->addOrderBy(
                'o.meeting_number',
                'ASC',
            )
            ->addOrderBy(
                'o.decision_point',
                'ASC',
            )
            ->addOrderBy(
                'o.decision_number',
                'ASC',
            )
            ->addOrderBy(
                'o.sequence',
                'ASC',
            )
            ->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize);

        return new Paginator(
            $qb->getQuery(),
            false,
        );
    }

    /**
     * The bodies that exist: founded, not abrogated, and their founding decision not annulled.
     *
     * A body is not an entity of its own, so "exists" is three conditions on the decision graph rather than a column.
     */
    private function overviewQuery(
        string $search,
        bool $includeAbrogated = false,
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('o');

        // The two ways of matching are grouped: `A OR B AND C` binds the AND tighter, so without the grouping a
        // body whose name matched came back even when the conditions below excluded it.
        $qb->addSelect(
            'd',
            'm',
        )
            ->andWhere($qb->expr()->orX(
                'LOWER(o.name) LIKE :name',
                'LOWER(o.abbr) LIKE :name',
            ))
            ->join(
                'o.decision',
                'd',
            )
            ->join(
                'd.meeting',
                'm',
            );

        // annulled foundation decisions
        $qbd = $this->getEntityManager()->createQueryBuilder();
        $qbd->select('b')
            ->from(
                Annulment::class,
                'b',
            )
            ->join(
                'b.target',
                'y',
            )
            ->where('y.meeting_type = o.meeting_type')
            ->andWhere('y.meeting_number = o.meeting_number')
            ->andWhere('y.point = o.decision_point')
            ->andWhere('y.number = o.decision_number');

        $qb->andWhere($qb->expr()->not(
            $qb->expr()->exists($qbd->getDQL()),
        ));

        if (!$includeAbrogated) {
            // we want to leave out bodies that have been abrogated
            $qbn = $this->getEntityManager()->createQueryBuilder();
            $qbn->select('a')
                ->from(
                    Abrogation::class,
                    'a',
                )
                ->join(
                    'a.foundation',
                    'x',
                )
                ->where('x.meeting_type = o.meeting_type')
                ->andWhere('x.meeting_number = o.meeting_number')
                ->andWhere('x.decision_point = o.decision_point')
                ->andWhere('x.decision_number = o.decision_number')
                ->andWhere('x.sequence = o.sequence');

            // leave out annulled abrogation decisions
            $qbnd = $this->getEntityManager()->createQueryBuilder();
            $qbnd->select('c')
                ->from(
                    Annulment::class,
                    'c',
                )
                ->join(
                    'c.target',
                    'z',
                )
                ->where('z.meeting_type = a.meeting_type')
                ->andWhere('z.meeting_number = a.meeting_number')
                ->andWhere('z.point = a.decision_point')
                ->andWhere('z.number = a.decision_number');

            $qbn->andWhere($qbn->expr()->not(
                $qbn->expr()->exists($qbnd->getDQL()),
            ));

            // add the subexpression
            $qb->andWhere($qb->expr()->not(
                $qb->expr()->exists(
                    $qbn->getDQL(),
                ),
            ));
        }

        $qb->setParameter(
            ':name',
            '%' . strtolower($search) . '%',
        );

        return $qb;
    }

    /**
     * Returns a subquery of all members IDs that were active within a certain timeframe.
     *
     * Has manual joins because of limitations with composite primary keys and doctrine.
     *
     * @param bool         $inActiveIsActive Whether to include members that are inactive organ members.
     * @param QueryBuilder $qb               The qb to use. Parameters will be set on this query builder.
     *
     * @return QueryBuilder A sub query builder that selects all member IDs that were active within the given timeframe.
     */
    public static function getIsActiveWithinSubQuery(
        QueryBuilder $qb,
        DateTimeInterface $activeAfter,
        DateTimeInterface $activeBefore,
        string $installAlias = 'installation',
        string $dischargeAlias = 'discharge',
        string $parameterPrefix = 'iaw',
        bool $inActiveIsActive = false,
    ): QueryBuilder {
        $sq = $qb->getEntityManager()->createQueryBuilder();

        // We take all unique members with installations
        $sq->select('IDENTITY(' . $installAlias . '.member)')
            ->distinct()
            ->from(
                Installation::class,
                $installAlias,
            )
            ->join(
                $installAlias . '.decision',
                $installAlias . 'Decision',
            )->join(
                $installAlias . 'Decision.meeting',
                $installAlias . 'Meeting',
            )->leftJoin(
                $installAlias . 'Decision.annulledBy',
                $installAlias . 'Annulment',
            );

        // Where the installation is before the activeBefore date
        $sq->andWhere($sq->expr()->lte($installAlias . 'Meeting.date', ':' . $parameterPrefix . 'ActiveBefore'));
        $qb->setParameter(
            $parameterPrefix . 'ActiveBefore',
            $activeBefore,
        );

        // And the installation was never annulled
        $sq->andWhere($sq->expr()->isNull($installAlias . 'Annulment.sequence'));

        // And the installation is not for an Inactief Lid
        if (!$inActiveIsActive) {
            $sq->andWhere($sq->expr()->neq($installAlias . '.function', ':' . $parameterPrefix . 'InactiveMember'));
            $qb->setParameter(
                $parameterPrefix . 'InactiveMember',
                InstallationFunctions::InactiveMember->value,
            );
        }

        // Where there is no discharge (or only an annulled discharge), or the discharge is after the activeAfter date
        // We need manual joins here because of limitations in doctrine
        $sq->leftJoin(
            $installAlias . '.discharge',
            $dischargeAlias,
        )->leftJoin(
            $dischargeAlias . '.decision',
            $dischargeAlias . 'Decision',
        )->leftJoin(
            $dischargeAlias . 'Decision.meeting',
            $dischargeAlias . 'Meeting',
        )->leftJoin(
            $dischargeAlias . 'Decision.annulledBy',
            $dischargeAlias . 'Annulment',
        )->andWhere($sq->expr()->orX(
            $sq->expr()->isNull($dischargeAlias . 'Meeting.date'),
            $sq->expr()->gt(
                $dischargeAlias . 'Meeting.date',
                ':' . $parameterPrefix . 'ActiveAfter',
            ),
            $sq->expr()->isNotNull($dischargeAlias . 'Annulment.sequence'),
        ));
        $qb->setParameter(
            $parameterPrefix . 'ActiveAfter',
            $activeAfter,
        );

        return $sq;
    }
}
