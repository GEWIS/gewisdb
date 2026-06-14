<?php

declare(strict_types=1);

namespace Database\Model;

use Database\Model\Enums\Studies;
use Database\Model\SubDecision\Installation;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;
use Laminas\Mail\Address as MailAddress;
use RuntimeException;

use function is_string;
use function mb_encode_mimeheader;

/**
 * Member model.
 */
#[Entity]
class Member
{
    /**
     * The user
     */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    private int $lidnr;

    /**
     * Member's email address.
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    private ?string $email = null;

    /**
     * Member's last name.
     */
    #[Column(type: 'string')]
    private string $lastName;

    /**
     * Middle name.
     */
    #[Column(type: 'string')]
    private string $middleName;

    /**
     * Initials.
     */
    #[Column(type: 'string')]
    private string $initials;

    /**
     * First name.
     */
    #[Column(type: 'string')]
    private string $firstName;

    /**
     * TU/e username.
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    private ?string $tueUsername = null;

    /**
     * Study of the member.
     */
    #[Column(
        enumType: Studies::class,
    )]
    private Studies $study = Studies::Unknown;

    /**
     * Last changed date of member.
     */
    #[Column(type: 'date')]
    private DateTime $changedOn;

    /**
     * Memberships of this member
     *
     * @var Collection<array-key, Membership>
     */
    #[OneToMany(
        targetEntity: Membership::class,
        mappedBy: 'member',
        cascade: ['persist', 'remove'],
    )]
    private Collection $memberships;

    /**
     * Last date membership status was checked.
     */
    #[Column(
        type: 'date',
        nullable: true,
    )]
    private ?DateTime $lastCheckedOn = null;

    /**
     * Member birthdate.
     */
    #[Column(type: 'date')]
    private DateTime $birth;

    /**
     * If the member receives a 'supremum'
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    private ?string $supremum = null;

    /**
     * Stores whether a member should be 'hidden'.
     *
     * Hidden is implemented in gewisweb to lock logins and hide the birthday on the landing page. It can be used for
     * deleted members and members that are deceased but whose profile should be kept.
     */
    #[Column(
        type: 'boolean',
        options: ['default' => false],
    )]
    private bool $hidden = false;

    /**
     * Addresses of this member.
     *
     * @var Collection<array-key, Address>
     */
    #[OneToMany(
        targetEntity: Address::class,
        mappedBy: 'member',
        cascade: ['persist', 'remove'],
    )]
    private Collection $addresses;

    /**
     * Installations of this member.
     *
     * @var Collection<array-key, Installation>
     */
    #[OneToMany(
        targetEntity: Installation::class,
        mappedBy: 'member',
    )]
    private Collection $installations;

    /**
     * Memberships of mailing lists.
     *
     * @var Collection<array-key, MailingListMember>
     */
    #[OneToMany(
        targetEntity: MailingListMember::class,
        mappedBy: 'member',
        cascade: ['persist'],
    )]
    private Collection $mailingListMemberships;

    /**
     * RenewalLinks of this member.
     *
     * @var Collection<array-key, RenewalLink>
     */
    #[OneToMany(
        targetEntity: RenewalLink::class,
        mappedBy: 'member',
        cascade: ['persist', 'remove'],
    )]
    private Collection $renewalLinks;

    /**
     * Audit entries (e.g. notes) of this member.
     *
     * @var Collection<array-key, AuditEntry>
     */
    #[OneToMany(
        targetEntity: AuditEntry::class,
        mappedBy: 'member',
        cascade: ['persist', 'remove'],
    )]
    private Collection $auditEntries;

    /**
     * A multiple-use authentication key which can be used in linked systems to verify updates
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    private ?string $authenticationKey = null;

    /**
     * Determines if a member is deleted. A deleted member is a member whose basic info needs to be retained to ensure
     * that all decisions that mention this member can be kept (i.e., administrative purposes). This value is only set
     * when deleting a member and cannot be altered via the interface.
     *
     * Additionally, this flag can be used to filter deleted members in external services (e.g., GEWISWEB).
     */
    #[Column(
        type: 'boolean',
        options: ['default' => false],
    )]
    private bool $deleted = false;

    public function __construct()
    {
        $this->addresses = new ArrayCollection();
        $this->installations = new ArrayCollection();
        $this->mailingListMemberships = new ArrayCollection();
        $this->memberships = new ArrayCollection();
    }

    /**
     * Get the membership number.
     */
    public function getLidnr(): int
    {
        return $this->lidnr;
    }

    /**
     * Get the member's email address.
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Get the member as an email recipient
     */
    public function getEmailRecipient(): ?MailAddress
    {
        if (null === $this->getEmail()) {
            return null;
        }

        return new MailAddress(
            $this->getEmail(),
            mb_encode_mimeheader(
                $this->getFullName(),
                'UTF-8',
                'Q',
                '',
            ),
        );
    }

    /**
     * Get the member's last name.
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * Get the member's middle name.
     */
    public function getMiddleName(): string
    {
        return $this->middleName;
    }

    /**
     * Get the member's initials.
     */
    public function getInitials(): string
    {
        return $this->initials;
    }

    /**
     * Get the member's first name.
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * Set the lidnr.
     */
    public function setLidnr(int $lidnr): void
    {
        $this->lidnr = $lidnr;
    }

    /**
     * Set the member's email address.
     */
    public function setEmail(?string $newEmail): void
    {
        // If the new email address matches the current, we don't have to do anything
        if ($this->email === $newEmail) {
            return;
        }

        $oldEmail = $this->email;
        $this->email = $newEmail;

        if (null === $oldEmail) {
            return;
        }

        $mailAddressExists = $this->mailingListMemberships->exists(
            static function ($key, MailingListMember $list) use ($newEmail) {
                return $newEmail === $list->getEmail();
            },
        );
        if ($mailAddressExists) {
            throw new RuntimeException(
                // phpcs:ignore -- user-visible strings should not be split
                'The e-mail address cannot be updated while there are already (pending) registrations for this member using this email address. Please try again once all list updates have been processed.',
            );
        }

        // For each mailing list memberships, schedule deletion of the old email and
        // registration using the new email address
        // Will be persisted with the member
        foreach ($this->mailingListMemberships as $mailingListMembership) {
            if ($mailingListMembership->isToBeDeleted()) {
                continue;
            }

            $mailingListMembership->setToBeDeleted(true);
            $newMembership = new MailingListMember();
            $newMembership->setMember($this);
            $newMembership->setEmail($newEmail);
            $newMembership->setMailingList($mailingListMembership->getMailingList());
            $this->addList($newMembership);
        }
    }

    /**
     * Set the member's last name.
     */
    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    /**
     * Set the member's middle name.
     */
    public function setMiddleName(string $middleName): void
    {
        $this->middleName = $middleName;
    }

    /**
     * Set the member's initials.
     */
    public function setInitials(string $initials): void
    {
        $this->initials = $initials;
    }

    /**
     * Set the member's first name.
     */
    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    /**
     * Assemble the member's full name.
     */
    public function getFullName(): string
    {
        $name = $this->getFirstName() . ' ';

        $middle = $this->getMiddleName();
        if (!empty($middle)) {
            $name .= $middle . ' ';
        }

        return $name . $this->getLastName();
    }

    /**
     * Get the generation of the member.
     */
    public function getGeneration(): int
    {
        $oldestMembership = null;

        foreach ($this->getMemberships() as $membership) {
            if (null !== $oldestMembership && $membership->getStartDate() >= $oldestMembership->getStartDate()) {
                continue;
            }

            $oldestMembership = $membership;
        }

        if (null === $oldestMembership) {
            return 0;
        }

        $startDate = $oldestMembership->getStartDate();

        if ($startDate->format('m') < 7) {
            return (int) $startDate->format('Y') - 1;
        }

        return (int) $startDate->format('Y');
    }

    /**
     * Get the TU/e username.
     */
    public function getTueUsername(): ?string
    {
        return $this->tueUsername;
    }

    /**
     * Set the TU/e username.
     */
    public function setTueUsername(?string $tueUsername): void
    {
        $this->tueUsername = $tueUsername;
    }

    /**
     * Get the study.
     */
    public function getStudy(): Studies
    {
        return $this->study;
    }

    /**
     * Set the study.
     */
    public function setStudy(Studies $study): void
    {
        $this->study = $study;
    }

    /**
     * Get the expiration date.
     */
    public function getExpiration(): DateTime
    {
        return $this->computeMembershipEndDate(formalMemberOnly: false) ?? new DateTime('0001-01-01 00:00:00');
    }

    /**
     * Get the birthdate.
     */
    public function getBirth(): DateTime
    {
        return $this->birth;
    }

    /**
     * Set the birthdate.
     */
    public function setBirth(DateTime|string $birth): void
    {
        if (is_string($birth)) {
            $birth = new DateTime($birth);
        }

        $this->birth = $birth;
    }

    /**
     * Get the date of the last member change.
     */
    public function getChangedOn(): DateTime
    {
        return $this->changedOn;
    }

    /**
     * Set the date of the last member change.
     */
    public function setChangedOn(DateTime $changedOn): void
    {
        $this->changedOn = $changedOn;
    }

    /**
     * Get the date on which the membership of the member will have ended (i.e., they have become "graduate").
     */
    public function getMembershipEndsOn(): DateTime
    {
        return $this->computeMembershipEndDate(formalMemberOnly: true) ?? new DateTime('0001-01-01 00:00:00');
    }

    /**
     * Compute the date of end of membership, or null if none
     */
    private function computeMembershipEndDate(bool $formalMemberOnly): ?DateTime
    {
        $expiration = null;

        foreach ($this->getMemberships() as $membership) {
            if (!$membership->getType()->isFormalMember() && $formalMemberOnly) {
                continue;
            }

            if (null !== $expiration && $membership->getEndDate() <= $expiration) {
                continue;
            }

            $expiration = $membership->getEndDate();
        }

        return $expiration;
    }

    /**
     * Get the memberships of this member.
     *
     * @return Collection<array-key, Membership>
     */
    public function getMemberships(): Collection
    {
        return $this->memberships;
    }

    /**
     * Add a membership to this member.
     */
    public function addMembership(Membership $membership): void
    {
        if ($membership->getMember() !== $this) {
            throw new RuntimeException('Membership does not belong to this member.');
        }

        $this->memberships[] = $membership;
    }

    /**
     * Delete all memberships of this member.
     * This is only used in the case of clearing/deleting a member.
     * In all other cases, the membership should be updated.
     */
    public function unsetMemberships(): void
    {
        foreach ($this->getMemberships() as $membership) {
            $this->memberships->removeElement($membership);
        }
    }

    /**
     * Get the current membership of this member, or null if none.
     */
    public function getCurrentMembership(): ?Membership
    {
        foreach ($this->getMemberships() as $membership) {
            if ($membership->isCurrent()) {
                return $membership;
            }
        }

        return null;
    }

    /**
     * Get the current membership of this member, or the last if expired.
     */
    public function getCurrentOrLastMembership(): ?Membership
    {
        if (null !== $this->getCurrentMembership()) {
            return $this->getCurrentMembership();
        }

        return $this->getLastMembership();
    }

    /**
     * Get the last (potentially expired, potentially future) membership
     */
    public function getLastMembership(): ?Membership
    {
        $lastMembership = null;
        foreach ($this->getMemberships() as $membership) {
            if (null !== $lastMembership && $membership->getEndDate() <= $lastMembership->getEndDate()) {
                continue;
            }

            $lastMembership = $membership;
        }

        return $lastMembership;
    }

    /**
     * Get the date of when the membership status was last checked.
     */
    public function getLastCheckedOn(): ?DateTime
    {
        return $this->lastCheckedOn;
    }

    /**
     * Set the date of when the membership status was last checked.
     */
    public function setLastCheckedOn(?DateTime $lastCheckedOn): void
    {
        $this->lastCheckedOn = $lastCheckedOn;
    }

    /**
     * Get if the member wants a supremum.
     */
    public function getSupremum(): ?string
    {
        return $this->supremum;
    }

    /**
     * Set if the member wants a supremum.
     */
    public function setSupremum(?string $supremum): void
    {
        $this->supremum = $supremum;
    }

    /**
     * Get if the member is hidden.
     */
    public function getHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * Set if the member is hidden.
     */
    public function setHidden(bool $hidden): void
    {
        $this->hidden = $hidden;
    }

    /**
     * Get the installations.
     *
     * @return Collection<array-key, Installation>
     */
    public function getInstallations(): Collection
    {
        return $this->installations;
    }

    /**
     * Get audit entries related to this member.
     *
     * @return Collection<array-key, AuditEntry>
     */
    public function getAuditEntries(): Collection
    {
        return $this->auditEntries;
    }

    public function getAuthenticationKey(): ?string
    {
        return $this->authenticationKey;
    }

    public function setAuthenticationKey(?string $authenticationKey): void
    {
        $this->authenticationKey = $authenticationKey;
    }

    /**
     * Get if the member is deleted.
     */
    public function getDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * Set if the member is deleted.
     */
    public function setDeleted(bool $deleted): void
    {
        $this->deleted = $deleted;
    }

    /**
     * Convert most relevant items to array.
     *
     * @return array{
     *     lidnr: int,
     *     email: ?string,
     *     fullName: string,
     *     lastName: string,
     *     middleName: string,
     *     initials: string,
     *     firstName: string,
     *     generation: int,
     *     hidden: bool,
     *     deleted: bool,
     *     expiration: string,
     *     authenticationKey: ?string,
     * }
     */
    public function toArray(): array
    {
        return [
            'lidnr' => $this->getLidnr(),
            'email' => $this->getEmail(),
            'fullName' => $this->getFullName(),
            'lastName' => $this->getLastName(),
            'middleName' => $this->getMiddleName(),
            'initials' => $this->getInitials(),
            'firstName' => $this->getFirstName(),
            'generation' => $this->getGeneration(),
            'hidden' => $this->getHidden(),
            'deleted' => $this->getDeleted(),
            'expiration' => $this->getExpiration()->format(DateTimeInterface::ATOM),
            'authenticationKey' => $this->getAuthenticationKey(),
        ];
    }

    /**
     * Get all addresses.
     *
     * @return Collection<array-key, Address>
     */
    public function getAddresses(): Collection
    {
        return $this->addresses;
    }

    /**
     * Add multiple addresses.
     *
     * @param Address[] $addresses
     */
    public function addAddresses(array $addresses): void
    {
        foreach ($addresses as $address) {
            $this->addAddress($address);
        }
    }

    /**
     * Add an address.
     */
    public function addAddress(Address $address): void
    {
        $address->setMember($this);
        $this->addresses[] = $address;
    }

    /**
     * Get mailing list subscriptions.
     *
     * @return Collection<array-key, MailingListMember>
     */
    public function getMailingListMemberships(): Collection
    {
        return $this->mailingListMemberships;
    }

    /**
     * Add a mailing list subscription.
     */
    public function addList(MailingListMember $list): void
    {
        if ($this->mailingListMemberships->contains($list)) {
            return;
        }

        $list->setMember($this);
        $this->mailingListMemberships->add($list);
    }

    /**
     * Add multiple mailing lists.
     *
     * @param MailingListMember[] $lists
     */
    public function addLists(array $lists): void
    {
        foreach ($lists as $list) {
            $this->addList($list);
        }
    }

    /**
     * Set the home address.
     */
    public function setHomeAddress(Address $address): void
    {
        $this->addAddress($address);
    }

    /**
     * Set the student address.
     */
    public function setStudentAddress(Address $address): void
    {
        $this->addAddress($address);
    }

    /**
     * Get renewal links of a member
     *
     * @return Collection<array-key, RenewalLink>
     */
    public function getRenewalLinks(): Collection
    {
        return $this->renewalLinks;
    }

    public function hasActiveRenewalLink(): bool
    {
        return $this->getRenewalLinks()->exists(
            static function ($key, RenewalLink $renewalLink) {
                return !$renewalLink->linkExpired();
            },
        );
    }
}
