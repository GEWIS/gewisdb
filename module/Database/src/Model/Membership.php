<?php

declare(strict_types=1);

namespace Database\Model;

use Application\Model\Enums\MembershipTypes;
use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\UniqueConstraint;
use LogicException;

/**
 * Representing one membership (typically 1 year)
 */
#[Entity]
#[Index(
    name: 'membership_member_idx',
    columns: ['member_lidnr'],
)]
#[UniqueConstraint(
    name: 'membership_unique_idx',
    columns: ['member_lidnr', 'startDate'],
)]
class Membership
{
    /**
     * The membership ID.
     *
     * This is not a natural ID, but a surrogate ID, because the natural ID (member + startDate) is not
     * very convenient to use as an ID by lack of DateTime having a __toString()
     */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    private int $id;

    /**
     * The member this membership belongs to.
     */
    #[ManyToOne(
        targetEntity: Member::class,
        inversedBy: 'memberships',
    )]
    #[JoinColumn(
        name: 'member_lidnr',
        referencedColumnName: 'lidnr',
        onDelete: 'cascade',
        nullable: false,
    )]
    private Member $member;

    /**
     * The start date of the membership.
     */
    #[Column(type: 'date')]
    private DateTime $startDate;

    /**
     * The end date of the membership.
     */
    #[Column(type: 'date')]
    private DateTime $endDate;

    /**
     * How much the member has paid for membership. 0 by default.
     */
    #[Column(type: 'integer')]
    private int $paid = 0;

    /**
     * 'Member' type.
     *
     * This can be one of the following, as defined by the GEWIS articles of association (statuten):
     *
     * - ordinary
     * - external
     * - graduate (= not a member))
     * - honorary
     *
     * You can find the GEWIS statuten here: https://gewis.nl/association/regulations/articles-of-association.
     *
     * See artikel 7.
     */
    #[Column(
        enumType: MembershipTypes::class,
    )]
    private MembershipTypes $type;

    public function __construct(
        Member $member,
        MembershipTypes $type,
        ?DateTime $startDate = null,
        ?DateTime $endDate = null,
    ) {
        if (null === $startDate) {
            $startDate = new DateTime();
        }

        if (null === $endDate) {
            $endDate = clone $startDate;

            if ($endDate->format('m') >= 7) {
                $expirationYear = (int) $endDate->format('Y') + 1;
            } else {
                $expirationYear = (int) $endDate->format('Y');
            }

            if (MembershipTypes::Honorary === $type) {
                // Honorary memberships do not expire, so we set the expiration date to 100 years in the future.
                $expirationYear += 100;
            }

            $endDate->setDate($expirationYear, 7, 1);
        }

        $startDate->setTime(0, 0);
        $endDate->setTime(0, 0);
        $this->member = $member;
        $this->type = $type;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * Member associated with this membership (immutable).
     */
    public function getMember(): Member
    {
        return $this->member;
    }

    // member should be immutable, so no setter for it

    /**
     * Start date of this membership (immutable).
     */
    public function getStartDate(): DateTime
    {
        return $this->startDate;
    }

    // startDate should be immutable, so no setter for it

    /**
     * End date of this membership.
     */
    public function getEndDate(): DateTime
    {
        return $this->endDate;
    }

    public function setEndDate(DateTime $endDate): void
    {
        if ($endDate < $this->startDate) {
            throw new LogicException('End date cannot be before start date.');
        }

        if ($endDate > $this->endDate) {
            throw new LogicException('End date cannot be after current end date, create a new membership instead.');
        }

        $endDate->setTime(0, 0);
        $this->endDate = $endDate;
    }

    /**
     * Type of this membership, see MembershipTypes enum for possible values.
     */
    public function getType(): MembershipTypes
    {
        return $this->type;
    }

    public function setType(MembershipTypes $type): void
    {
        $this->type = $type;
    }

    /**
     * How much the member has paid for membership.
     */
    public function getPaid(): int
    {
        return $this->paid;
    }

    public function setPaid(int $paid): void
    {
        if ($paid < 0) {
            throw new LogicException('Paid amount cannot be negative.');
        }

        $this->paid = $paid;
    }

    /**
     * Check if the membership is currently active (i.e. the current date is between the start and end date).
     */
    public function isCurrent(): bool
    {
        $now = new DateTime();

        return $this->startDate <= $now && $this->endDate >= $now;
    }
}
