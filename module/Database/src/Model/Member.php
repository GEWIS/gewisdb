<?php

namespace Database\Model;

use Application\Model\Enums\MembershipTypes;
use Database\Model\SubDecision\Installation;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    GeneratedValue,
    Id,
    InverseJoinColumn,
    JoinColumn,
    JoinTable,
    ManyToMany,
    OneToMany,
};
use Doctrine\Common\Collections\Collection;

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
    #[Column(type: "integer")]
    #[GeneratedValue(strategy: "AUTO")]
    protected ?int $lidnr = null;

    /**
     * Member's email address.
     */
    #[Column(
        type: "string",
        nullable: true,
    )]
    protected ?string $email = null;

    /**
     * Member's last name.
     */
    #[Column(type: "string")]
    protected string $lastName;

    /**
     * Middle name.
     */
    #[Column(type: "string")]
    protected string $middleName;

    /**
     * Initials.
     */
    #[Column(type: "string")]
    protected string $initials;

    /**
     * First name.
     */
    #[Column(type: "string")]
    protected string $firstName;

    /**
     * Generation.
     *
     * This is the year that this member became a GEWIS member. This is not
     * a academic year, but rather a calendar year.
     */
    #[Column(type: "integer")]
    protected int $generation;

    /**
     * TU/e username.
     */
    #[Column(
        type: "string",
        nullable: true,
    )]
    protected ?string $tueUsername = null;

    /**
     * Study of the member.
     */
    #[Column(
        type: "string",
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
     * You can find the GEWIS statuten here: https://gewis.nl/vereniging/statuten/statuten.
     *
     * See artikel 7.
     */
    #[Column(
        type: "string",
        enumType: MembershipTypes::class,
    )]
    protected MembershipTypes $type;

    /**
     * Last changed date of membership.
     */
    #[Column(type: "date")]
    protected DateTime $changedOn;

    /**
     * Keeps track of whether a student is still studying (either at the Department of Mathematics and Computer Science,
     * the TU/e in general, or another institution).
     */
    #[Column(type: "boolean")]
    protected bool $isStudying;

    /**
     * Date when the real membership ("ordinary" or "external") of the member will have ended, in other words, from this
     * date onwards they are "graduate". If `null`, the expiration is rolling and will be silently renewed if the member
     * still meets the requirements as set forth in the bylaws and internal regulations.
     */
    #[Column(
        type: "date",
        nullable: true,
    )]
    protected ?DateTime $membershipEndsOn = null;

    /**
     * The date on which the membership of the member is set to expire and will therefore have to be renewed, which
     * happens either automatically or has to be done manually, as set forth in the bylaws and internal regulations.
     */
    #[Column(type: "date")]
    protected DateTime $expiration;

    /**
     * Last date membership status was checked.
     */
    #[Column(
        type: "date",
        nullable: true,
    )]
    protected ?DateTime $lastCheckedOn = null;

    /**
     * Member birth date.
     */
    #[Column(type: "date")]
    protected DateTime $birth;

    /**
     * How much the member has paid for membership. 0 by default.
     */
    #[Column(type: "integer")]
    protected int $paid = 0;

    /**
     * Iban number.
     */
    #[Column(
        type: "string",
        nullable: true,
    )]
    protected ?string $iban = null;

    /**
     * If the member receives a 'supremum'
     */
    #[Column(
        type: "string",
        nullable: true,
    )]
    protected ?string $supremum = null;

    /**
     * Stores whether a member should be 'hidden'
     * Hidden is implemented in gewisweb to lock logins and hide the birthday on the landing page
     * It can be used for deleted members and members that are deceased but whose profile should be kept
     */
    #[Column(
        type: "boolean",
        options: ['default' => false]
    )]
    protected bool $hidden = false;

    /**
     * Addresses of this member.
     */
    #[OneToMany(
        targetEntity: Address::class,
        mappedBy: "member",
        cascade: ["persist", "remove"],
    )]
    protected Collection $addresses;

    /**
     * Installations of this member.
     */
    #[OneToMany(
        targetEntity: Installation::class,
        mappedBy: "member",
    )]
    protected Collection $installations;

    /**
     * Memberships of mailing lists.
     */
    #[ManyToMany(
        targetEntity: MailingList::class,
        inversedBy: "members",
    )]
    #[JoinTable(name: "members_mailinglists")]
    #[JoinColumn(
        name: "lidnr",
        referencedColumnName: "lidnr",
    )]
    #[InverseJoinColumn(
        name: "name",
        referencedColumnName: "name",
    )]
    protected Collection $lists;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->addresses = new ArrayCollection();
        $this->installations = new ArrayCollection();
        $this->lists = new ArrayCollection();
    }

    /**
     * Get the membership number.
     *
     * @return int|null
     */
    public function getLidnr(): ?int
    {
        return $this->lidnr;
    }

    /**
     * Get the member's email address.
     *
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Get the member's last name.
     *
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * Get the member's middle name.
     *
     * @return string
     */
    public function getMiddleName(): string
    {
        return $this->middleName;
    }

    /**
     * Get the member's initials.
     *
     * @return string
     */
    public function getInitials(): string
    {
        return $this->initials;
    }

    /**
     * Get the member's first name.
     *
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * Set the lidnr.
     *
     * @param int $lidnr
     */
    public function setLidnr(int $lidnr): void
    {
        $this->lidnr = $lidnr;
    }

    /**
     * Set the member's email address.
     *
     * @param string|null $email
     */
    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    /**
     * Set the member's last name.
     *
     * @param string $lastName
     */
    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    /**
     * Set the member's middle name.
     *
     * @param string $middleName
     */
    public function setMiddleName(string $middleName): void
    {
        $this->middleName = $middleName;
    }

    /**
     * Set the member's initials.
     *
     * @param string $initials
     */
    public function setInitials(string $initials): void
    {
        $this->initials = $initials;
    }

    /**
     * Set the member's first name.
     *
     * @param string $firstName
     */
    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    /**
     * Assemble the member's full name.
     *
     * @return string
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
     *
     * @return int
     */
    public function getGeneration(): int
    {
        return $this->generation;
    }

    /**
     * Set the generation.
     *
     * @param int $generation
     */
    public function setGeneration(int $generation): void
    {
        $this->generation = $generation;
    }

    /**
     * Get the TU/e username.
     *
     * @return string|null
     */
    public function getTueUsername(): ?string
    {
        return $this->tueUsername;
    }

    /**
     * Set the TU/e username.
     *
     * @param string|null $tueUsername
     */
    public function setTueUsername(?string $tueUsername): void
    {
        $this->tueUsername = $tueUsername;
    }

    /**
     * Get the study.
     *
     * @return string|null
     */
    public function getStudy(): ?string
    {
        return $this->study;
    }

    /**
     * Set the study.
     *
     * @param string|null $study
     */
    public function setStudy(?string $study): void
    {
        $this->study = $study;
    }

    /**
     * Get the member type.
     *
     * @return MembershipTypes
     */
    public function getType(): MembershipTypes
    {
        return $this->type;
    }

    /**
     * Set the member type.
     *
     * @param MembershipTypes $type
     */
    public function setType(MembershipTypes $type): void
    {
        $this->type = $type;
    }

    /**
     * Get the expiration date.
     *
     * @return DateTime
     */
    public function getExpiration(): DateTime
    {
        return $this->expiration;
    }

    /**
     * Set the expiration date.
     *
     * @param DateTime $expiration
     *
     * @return void
     */
    public function setExpiration(DateTime $expiration): void
    {
        $this->expiration = $expiration;
    }

    /**
     * Get the birth date.
     *
     * @return DateTime
     */
    public function getBirth(): DateTime
    {
        return $this->birth;
    }

    /**
     * Set the birthdate.
     *
     * @param DateTime $birth
     */
    public function setBirth(DateTime $birth): void
    {
        $this->birth = $birth;
    }

    /**
     * Get the date of the last membership change.
     *
     * @return DateTime
     */
    public function getChangedOn(): DateTime
    {
        return $this->changedOn;
    }

    /**
     * Set the date of the last membership change.
     *
     * @param DateTime $changedOn
     */
    public function setChangedOn(DateTime $changedOn): void
    {
        $this->changedOn = $changedOn;
    }

    /**
     * Get whether the member is still studying.
     *
     * @return bool
     */
    public function getIsStudying(): bool
    {
        return $this->isStudying;
    }

    /**
     * Set whether the member is still studying.
     *
     * @param bool $isStudying
     */
    public function setIsStudying(bool $isStudying): void
    {
        $this->isStudying = $isStudying;
    }

    /**
     * Get the date on which the membership of the member will have ended (i.e., they have become "graduate").
     *
     * @return DateTime|null
     */
    public function getMembershipEndsOn(): ?DateTime
    {
        return $this->membershipEndsOn;
    }

    /**
     * Set the date on which the membership of the member will have ended (i.e., they have become "graduate").
     *
     * @param DateTime|null $membershipEndsOn
     */
    public function setMembershipEndsOn(?DateTime $membershipEndsOn): void
    {
        $this->membershipEndsOn = $membershipEndsOn;
    }

    /**
     * Get the date of when the membership status was last checked.
     *
     * @return DateTime|null
     */
    public function getLastCheckedOn(): ?DateTime
    {
        return $this->lastCheckedOn;
    }

    /**
     * Set the date of when the membership status was last checked.
     *
     * @param DateTime|null $lastCheckedOn
     */
    public function setLastCheckedOn(?DateTime $lastCheckedOn): void
    {
        $this->lastCheckedOn = $lastCheckedOn;
    }

    /**
     * Get how much has been paid.
     *
     * @return int
     */
    public function getPaid(): int
    {
        return $this->paid;
    }

    /**
     * Set how much has been paid.
     *
     * @param int $paid
     */
    public function setPaid(int $paid): void
    {
        $this->paid = $paid;
    }

    /**
     * Get the IBAN.
     *
     * @return string|null
     */
    public function getIban($print = false): ?string
    {
        if (null === $this->iban) {
            return null;
        }
        if ($print) {
            return preg_replace('/(\\w{4})/', '$1 ', $this->iban);
        }
        return $this->iban;
    }

    /**
     * Set the IBAN.
     *
     * @param string|null $iban
     */
    public function setIban(?string $iban): void
    {
        $this->iban = $iban;
    }

    /**
     * Get if the member wants a supremum.
     *
     * @return string|null
     */
    public function getSupremum(): ?string
    {
        return $this->supremum;
    }

    /**
     * Set if the member wants a supremum.
     *
     * @param string|null $supremum
     */
    public function setSupremum(?string $supremum): void
    {
        $this->supremum = $supremum;
    }

    /**
     * Get if the member is hidden.
     *
     * @return bool
     */
    public function getHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * Set if the member is hidden.
     *
     * @param bool $hidden
     */
    public function setHidden(bool $hidden): void
    {
        $this->hidden = $hidden;
    }

    /**
     * Get the installations.
     *
     * @return Collection
     */
    public function getInstallations(): Collection
    {
        return $this->installations;
    }

    /**
     * Convert most relevant items to array.
     *
     * @return array
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
            'expiration' => $this->getExpiration()->format('l j F Y'),
        ];
    }

    /**
     * Get all addresses.
     *
     * @return Collection all addresses
     */
    public function getAddresses(): Collection
    {
        return $this->addresses;
    }

    /**
     * Add multiple addresses.
     *
     * @param array $addresses
     */
    public function addAddresses(array $addresses): void
    {
        foreach ($addresses as $address) {
            $this->addAddress($address);
        }
    }

    /**
     * Add an address.
     *
     * @param Address $address
     */
    public function addAddress(Address $address): void
    {
        $address->setMember($this);
        $this->addresses[] = $address;
    }

    /**
     * Get mailing list subscriptions.
     *
     * @return Collection
     */
    public function getLists(): Collection
    {
        return $this->lists;
    }

    /**
     * Add a mailing list subscription.
     *
     * Note that this is the owning side.
     *
     * @param MailingList $list
     */
    public function addList(MailingList $list): void
    {
        $list->addMember($this);
        $this->lists[] = $list;
    }

    /**
     * Add multiple mailing lists.
     *
     * @param array $lists
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
     *
     * @param Address $address
     */
    public function setHomeAddress(Address $address): void
    {
        $this->addAddress($address);
    }

    /**
     * Set the student address.
     *
     * @param Address $address
     */
    public function setStudentAddress(Address $address): void
    {
        $this->addAddress($address);
    }
}
