<?php

declare(strict_types=1);

namespace Database\Mapper;

use Database\Model\CheckoutSession as CheckoutSessionModel;
use Database\Model\Enums\CheckoutSessionStates;
use Database\Model\ProspectiveMember as ProspectiveMemberModel;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use function count;
use function is_numeric;
use function str_replace;
use function strtolower;

class ProspectiveMember
{
    public function __construct(protected readonly EntityManager $em)
    {
    }

    /**
     * See if we can find a member with the same email.
     */
    public function hasMemberWith(string $email): bool
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('m')
            ->from(ProspectiveMemberModel::class, 'm')
            ->where('LOWER(m.email) = LOWER(:email)')
            ->setMaxResults(1);

        $qb->setParameter(':email', $email);

        $ret = $qb->getQuery()->getResult();

        return null !== $ret && count($ret) > 0;
    }

    /**
     * Search for a member.
     *
     * @return array<array-key, ProspectiveMemberModel>
     */
    public function search(
        string $query,
        string $type,
    ): array {
        $qb = $this->em->createQueryBuilder();

        $qb->select('m')
            ->from(ProspectiveMemberModel::class, 'm')
            ->where("CONCAT(LOWER(m.firstName), ' ', LOWER(m.lastName)) LIKE :name")
            ->orWhere("CONCAT(LOWER(m.firstName), ' ', LOWER(m.middleName), ' ', LOWER(m.lastName)) LIKE :name")
            ->orWhere('m.email LIKE :name')
            ->setMaxResults(128)
            ->orderBy('m.lidnr', 'DESC')
            ->setFirstResult(0);

        $qb->setParameter(':name', '%' . strtolower($query) . '%');

        // also allow searching for membership number
        if (is_numeric($query)) {
            $qb->orWhere('m.lidnr = :nr');
            $qb->orWhere('m.tueUsername = :nr');
            $qb->setParameter(':nr', $query);
        }

        // Get Checkout Session status.
        $qb->leftJoin(CheckoutSessionModel::class, 'cs', Join::WITH, 'cs.prospectiveMember = m.lidnr');
        $qbc = $this->em->createQueryBuilder();
        $qbc->select('MAX(css.id)')
            ->from(CheckoutSessionModel::class, 'css')
            ->where('css.prospectiveMember = m.lidnr');
        $qb->andWhere($qb->expr()->orX(
            $qb->expr()->eq('cs.id', '(' . $qbc->getDQL() . ')'),
            $qb->expr()->isNull('cs.id'),
        ));

        if ('paid' === $type) {
            $qb->andWhere('cs.state = :paid')
                ->setParameter('paid', CheckoutSessionStates::Paid);
        } elseif ('failed' === $type) {
            $qb->andWhere('cs.state = :expired OR cs.state = :failed OR cs.state IS NULL')
                ->setParameter('expired', CheckoutSessionStates::Expired)
                ->setParameter('failed', CheckoutSessionStates::Failed);
        } else {
            $qb->andWhere('cs.state = :created OR cs.state = :pending')
                ->setParameter('created', CheckoutSessionStates::Created)
                ->setParameter('pending', CheckoutSessionStates::Pending);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all members.
     *
     * @return array<array-key, ProspectiveMemberModel>
     */
    public function findAll(): array
    {
        return $this->getRepository()->findAll();
    }

    /**
     * Find a member (by lidnr).
     *
     * And calculate memberships.
     */
    public function find(int $lidnr): ?ProspectiveMemberModel
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('m, l')
            ->from(ProspectiveMemberModel::class, 'm')
            ->where('m.lidnr = :lidnr')
            ->leftJoin('m.lists', 'l');

        $qb->setParameter(':lidnr', $lidnr);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Find all prospective members whose last Checkout Session has fully expired ((1/24 + 30) + 1 day ago) or failed 31
     * days ago.
     *
     * @return ProspectiveMemberModel[]
     */
    public function findWithFullyExpiredOrFailedCheckout(): array
    {
        // Get all prospective members and their checkout sessions
        $qb = $this->getRepository()->createQueryBuilder('m');
        $qb->leftJoin(CheckoutSessionModel::class, 'cs', Join::WITH, 'cs.prospectiveMember = m.lidnr');

        // Subquery to get maximum checkout session for a member
        $qbc = $this->em->createQueryBuilder();
        $qbc->select('MAX(csm.id)')
            ->from(CheckoutSessionModel::class, 'csm')
            ->where('csm.prospectiveMember = m.lidnr');

        // Subquery to get the original (expired) checkout session (the one that could be recovered)
        $qbd = $this->em->createQueryBuilder();
        $qbd->select('(CASE WHEN css.recoveredFrom IS NOT NULL THEN IDENTITY(css.recoveredFrom) ELSE css.id END)')
            ->from(CheckoutSessionModel::class, 'css')
            ->where('css.prospectiveMember = m.lidnr')
            ->andWhere($qb->expr()->eq('css.id', '(' . str_replace('csm', 'csm2', $qbc->getDQL()) . ')'))
            ->andWhere('css.state = :expired');

        $qb->where($qb->expr()->orX(
            // Get the last checkout session, if it has failed more than 31 days ago
            $qb->expr()->andX(
                $qb->expr()->eq('cs.id', '(' . $qbc->getDQL() . ')'),
                $qb->expr()->eq('cs.state', ':failed'),
                $qb->expr()->lt('cs.expiration', ':fullyFailed'),
            ),
            // OR get the original session if it has expired more than a day ago using that
            // if x.state == Expired, the expiration date is the last date the checkout session can be recoverd
            $qb->expr()->andX(
                $qb->expr()->eq('cs.id', '(' . $qbd->getDQL() . ')'),
                $qb->expr()->eq('cs.state', ':expired'),
                $qb->expr()->lt('cs.expiration', ':fullyExpired'),
            ),
        ));

        $qb->setParameter('expired', CheckoutSessionStates::Expired)
            ->setParameter('failed', CheckoutSessionStates::Failed)
            ->setParameter('fullyExpired', (new DateTime())->sub(new DateInterval('P1D')))
            ->setParameter('fullyFailed', (new DateTime())->sub(new DateInterval('P31D')));

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all prospective members wihout any checkout session (should not happen)
     *
     * @return ProspectiveMemberModel[]
     */
    public function findWithoutCheckout(): array
    {
        // Get all checkout sessions
        $checkoutSessions = $this->em->createQueryBuilder();
        $checkoutSessions->select('pmwithcs.lidnr')
            ->from(CheckoutSessionModel::class, 'cs')
            ->innerJoin('cs.prospectiveMember', 'pmwithcs');

        // Get all prospective members without a checkout session that are there for more than 30 days
        $qb = $this->getRepository()->createQueryBuilder('m');
        $qb->where($qb->expr()->notIn('m.lidnr', $checkoutSessions->getDQL()))
            ->andWhere('m.changedOn <= :fullyExpired');

        $qb->setParameter('fullyExpired', (new DateTime())->sub(new DateInterval('P31D')));

        return $qb->getQuery()->getResult();
    }

    /**
     * Persist a member model.
     */
    public function persist(ProspectiveMemberModel $member): void
    {
        $this->em->persist($member);
        $this->em->flush();
    }

    /**
     * Remove a member.
     */
    public function remove(ProspectiveMemberModel $member): void
    {
        $this->em->remove($member);
        $this->em->flush();
    }

    /**
     * Get the repository for this mapper.
     */
    public function getRepository(): EntityRepository
    {
        return $this->em->getRepository(ProspectiveMemberModel::class);
    }
}
