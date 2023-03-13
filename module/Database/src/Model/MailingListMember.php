<?php

declare(strict_types=1);

namespace Database\Model;

use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * Mailing List Member model.
 *
 * To allow having additional properties in the many-to-many association between {@see MailingList}s and {@see Member}s
 * we use this class as a connector.
 *
 * Mailing list <-> member associations are never directly propagated to Mailman. When synchronizing the state directly,
 * the chances of something going wrong are too high. For example, we do not want someone to be registered in Mailman
 * for a list, but this is not directly visible in the database. What we do want is to always know in the database the
 * state of a member who is a member of a mailing list, as such persisting this entity is our highest priority.
 *
 * The actual synchronization should take place through cron jobs. To keep track of what is supposed to happen, the
 * additional properties in this entity are used for this.
 */
#[Entity]
#[UniqueConstraint(
    name: 'mailinglistmember_unique_idx',
    columns: ['mailingList', 'member'],
)]
class MailingListMember
{
    /**
     * Mailing list.
     */
    #[Id]
    #[ManyToOne(
        targetEntity: MailingList::class,
        inversedBy: 'mailingListMemberships',
    )]
    #[JoinColumn(
        name: 'mailingList',
        referencedColumnName: 'name',
    )]
    private MailingList $mailingList;

    /**
     * Member.
     */
    #[Id]
    #[ManyToOne(
        targetEntity: Member::class,
        inversedBy: 'mailingListMemberships',
    )]
    #[JoinColumn(
        name: 'member',
        referencedColumnName: 'lidnr',
    )]
    private Member $member;

    #[Column(
        type: 'string',
        nullable: true,
    )]
    private ?string $membershipId = null;

    /**
     * When this association was last synced to/from Mailman.
     */
    #[Column(
        type: 'datetime',
        nullable: true,
    )]
    protected ?DateTime $lastSyncOn = null;

    /**
     * Whether the last attempted sync was successful.
     *
     * At creation of the association, no sync has taken place (i.e. {@see MailingListMember::$lastSyncOn} is `null`) so
     * we default to `false`.
     */
    #[Column(type: 'boolean')]
    protected bool $lastSyncSuccess = false;

    /**
     * Whether this entry still needs to be removed from Mailman.
     *
     * It indicates that there is no longer an association between the mailing list and the member.
     */
    #[Column(type: 'boolean')]
    protected bool $toBeDeleted = false;

    public function __construct()
    {
    }

    /**
     * Get the mailing list.
     */
    public function getMailingList(): MailingList
    {
        return $this->mailingList;
    }

    /**
     * Set the mailing list.
     */
    public function setMailingList(MailingList $mailingList): void
    {
        $this->mailingList = $mailingList;
    }

    /**
     * Get the member.
     */
    public function getMember(): Member
    {
        return $this->member;
    }

    /**
     * Set the member.
     */
    public function setMember(Member $member): void
    {
        $this->member = $member;
    }

    /**
     * Get the Mailman `member_id` for this subscription.
     */
    public function getMembershipId(): ?string
    {
        return $this->membershipId;
    }

    /**
     * Set the Mailman `member_id` for this subscription.
     */
    public function setMembershipId(string $membershipId): void
    {
        $this->membershipId = $membershipId;
    }

    /**
     * Get when the last sync happened.
     */
    public function getLastSyncOn(): ?DateTime
    {
        return $this->lastSyncOn;
    }

    /**
     * Set when the last sync happened.
     */
    public function setLastSyncOn(DateTime $lastSyncOn): void
    {
        $this->lastSyncOn = $lastSyncOn;
    }

    /**
     * Get whether the last sync was successful.
     */
    public function isLastSyncSuccess(): bool
    {
        return $this->lastSyncSuccess;
    }

    /**
     * Set whether the last sync was successful.
     */
    public function setLastSyncSuccess(bool $lastSyncSuccess): void
    {
        $this->lastSyncSuccess = $lastSyncSuccess;
    }

    /**
     * Get whether the entry must still be removed from Mailman.
     */
    public function isToBeDeleted(): bool
    {
        return $this->toBeDeleted;
    }

    /**
     * Set whether the entry must still be removed from Mailman.
     */
    public function setToBeDeleted(bool $toBeDeleted): void
    {
        $this->toBeDeleted = $toBeDeleted;
    }
}
