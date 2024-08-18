<?php

declare(strict_types=1);

namespace Database\Model;

use Application\Model\Enums\MembershipTypes;
use Database\Model\SubDecision\Installation;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InverseJoinColumn;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\OneToMany;
use Laminas\Mail\Address as MailAddress;

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
    protected ?int $lidnr = null;

    /**
     * Member's email address.
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    protected ?string $email = null;

    /**
     * Member's last name.
     */
    #[Column(type: 'string')]
    protected string $lastName;

    /**
     * Middle name.
     */
    #[Column(type: 'string')]
    protected string $middleName;

    /**
     * Initials.
     */
    #[Column(type: 'string')]
    protected string $initials;

    /**
     * First name.
     */
    #[Column(type: 'string')]
    protected string $firstName;

    /**
     * Generation.
     *
     * This is the year that this member became a GEWIS member. This is not
     * a academic year, but rather a calendar year.
     */
    #[Column(type: 'integer')]
    protected int $generation;

    /**
     * TU/e username.
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    protected ?string $tueUsername = null;

    /**
     * Study of the member.
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    protected ?string $study = null;

    /**
     * Member type.
     *
     * This can be one of the following, as defined by the GEWIS statuten:
     *
     * - ordinary
     * - external
     * - graduate
     * - honorary
     *
     * You can find the GEWIS statuten here: https://gewis.nl/association/regulations/articles-of-association.
     *
     * See artikel 7.
     */
    #[Column(
        type: 'string',
        enumType: MembershipTypes::class,
    )]
    protected MembershipTypes $type;

    /**
     * Last changed date of membership.
     */
    #[Column(type: 'date')]
    protected DateTime $changedOn;

    /**
     * Keeps track of whether a student is still studying (either at the Department of Mathematics and Computer Science,
     * the TU/e in general, or another institution).
     */
    #[Column(type: 'boolean')]
    protected bool $isStudying;

    /**
     * Date when the real membership ("ordinary", "external" or "honorary") of the member will have ended, i.e., from
     * this date onwards they are "graduate". If `null`, the expiration is rolling and will be silently renewed if the
     * member still meets the requirements as set forth in the bylaws and internal regulations.
     */
    #[Column(
        type: 'date',
        nullable: true,
    )]
    protected ?DateTime $membershipEndsOn = null;

    /**
     * The date on which the membership of the member is set to expire and will therefore have to be renewed, which
     * happens either automatically or has to be done manually, as set forth in the bylaws and internal regulations.
     */
    #[Column(type: 'date')]
    protected DateTime $expiration;

    /**
     * Last date membership status was checked.
     */
    #[Column(
        type: 'date',
        nullable: true,
    )]
    protected ?DateTime $lastCheckedOn = null;

    /**
     * Member birthdate.
     */
    #[Column(type: 'date')]
    protected DateTime $birth;

    /**
     * How much the member has paid for membership. 0 by default.
     */
    #[Column(type: 'integer')]
    protected int $paid = 0;

    /**
     * If the member receives a 'supremum'
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    protected ?string $supremum = null;

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
    protected bool $hidden = false;

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
    protected Collection $addresses;

    /**
     * Installations of this member.
     *
     * @var Collection<array-key, Installation>
     */
    #[OneToMany(
        targetEntity: Installation::class,
        mappedBy: 'member',
    )]
    protected Collection $installations;

    /**
     * Memberships of mailing lists.
     *
     * @var Collection<array-key, MailingList>
     */
    #[ManyToMany(
        targetEntity: MailingList::class,
        inversedBy: 'members',
    )]
    #[JoinTable(name: 'members_mailinglists')]
    #[JoinColumn(
        name: 'lidnr',
        referencedColumnName: 'lidnr',
    )]
    #[InverseJoinColumn(
        name: 'name',
        referencedColumnName: 'name',
    )]
    protected Collection $lists;

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
    protected Collection $renewalLinks;

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
    protected Collection $auditEntries;

    /**
     * A multiple-use authentication key which can be used in linked systems to verify updates
     */
    #[Column(
        type: 'string',
        nullable: true,
    )]
    protected ?string $authenticationKey = null;

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
    protected bool $deleted = false;

    public function __construct()
    {
        $this->addresses = new ArrayCollection();
        $this->installations = new ArrayCollection();
        $this->lists = new ArrayCollection();
    }

    /**
     * Get the membership number.
     *
     * @psalm-ignore-nullable-return
     */
    public function getLidnr(): ?int
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
    public function setEmail(?string $email): void
    {
        $this->email = $email;
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
     * Get the generation.
     */
    public function getGeneration(): int
    {
        return $this->generation;
    }

    /**
     * Set the generation.
     */
    public function setGeneration(int $generation): void
    {
        $this->generation = $generation;
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
    public function getStudy(): ?string
    {
        return $this->study;
    }

    /**
     * Set the study.
     */
    public function setStudy(?string $study): void
    {
        $this->study = $study;
    }

    /**
     * Get the member type.
     */
    public function getType(): MembershipTypes
    {
        return $this->type;
    }

    /**
     * Set the member type.
     */
    public function setType(MembershipTypes $type): void
    {
        $this->type = $type;
    }

    /**
     * Get the expiration date.
     */
    public function getExpiration(): DateTime
    {
        return $this->expiration;
    }

    /**
     * Set the expiration date.
     */
    public function setExpiration(DateTime $expiration): void
    {
        $this->expiration = $expiration;
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
    public function setBirth(DateTime $birth): void
    {
        $this->birth = $birth;
    }

    /**
     * Get the date of the last membership change.
     */
    public function getChangedOn(): DateTime
    {
        return $this->changedOn;
    }

    /**
     * Set the date of the last membership change.
     */
    public function setChangedOn(DateTime $changedOn): void
    {
        $this->changedOn = $changedOn;
    }

    /**
     * Get whether the member is still studying.
     */
    public function getIsStudying(): bool
    {
        return $this->isStudying;
    }

    /**
     * Set whether the member is still studying.
     */
    public function setIsStudying(bool $isStudying): void
    {
        $this->isStudying = $isStudying;
    }

    /**
     * Get the date on which the membership of the member will have ended (i.e., they have become "graduate").
     */
    public function getMembershipEndsOn(): ?DateTime
    {
        return $this->membershipEndsOn;
    }

    /**
     * Set the date on which the membership of the member will have ended (i.e., they have become "graduate").
     */
    public function setMembershipEndsOn(?DateTime $membershipEndsOn): void
    {
        $this->membershipEndsOn = $membershipEndsOn;
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
     * Get how much has been paid.
     */
    public function getPaid(): int
    {
        return $this->paid;
    }

    /**
     * Set how much has been paid.
     */
    public function setPaid(int $paid): void
    {
        $this->paid = $paid;
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
     * @return Collection<array-key, MailingList>
     */
    public function getLists(): Collection
    {
        return $this->lists;
    }

    /**
     * Add a mailing list subscription.
     *
     * Note that this is the owning side.
     */
    public function addList(MailingList $list): void
    {
        $list->addMember($this);
        $this->lists[] = $list;
    }

    /**
     * Add multiple mailing lists.
     *
     * @param MailingList[] $lists
     */
    public function addLists(array $lists): void
    {
        foreach ($lists as $list) {
            $this->addList($list);
        }
    }

    /**
     * Clear the lists.
     */
    public function clearLists(): void
    {
        $this->lists = new ArrayCollection();
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
}
