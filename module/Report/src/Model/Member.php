<?php

declare(strict_types=1);

namespace Report\Model;

use Application\Model\Enums\MembershipTypes;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToMany;
use Report\Model\SubDecision\Installation;

use function array_filter;
use function array_map;
use function array_reduce;
use function array_values;
use function in_array;

/**
 * Member model.
 */
#[Entity]
class Member
{
    /**
     * The user.
     */
    #[Id]
    #[Column(type: 'integer')]
    #[JoinColumn(
        name: 'lidnr',
        referencedColumnName: 'lidnr',
    )]
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
     * Date when the real membership ("ordinary" or "external") of the member will have ended, in other words, from this
     * date onwards they are "graduate". If `null`, the expiration is rolling and will be silently renewed if the member
     * still meets the requirements as set forth in the bylaws and internal regulations.
     */
    #[Column(
        type: 'date',
        nullable: true,
    )]
    protected ?DateTime $membershipEndsOn = null;

    /**
     * Member birth date.
     */
    #[Column(type: 'date')]
    protected DateTime $birth;

    /**
     * The date on which the membership of the member is set to expire and will therefore have to be renewed, which
     * happens either automatically or has to be done manually, as set forth in the bylaws and internal regulations.
     */
    #[Column(type: 'date')]
    protected DateTime $expiration;

    /**
     * How much the member has paid for membership. 0 by default.
     */
    #[Column(type: 'integer')]
    protected int $paid = 0;

    /**
     * If the member receives a 'supremum'.
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
        cascade: ['persist'],
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
     * @var Collection<array-key, MailingListMember>
     */
    #[OneToMany(
        targetEntity: MailingListMember::class,
        mappedBy: 'member',
        cascade: ['persist'],
    )]
    protected Collection $mailingListMemberships;

    /**
     * Organ memberships.
     *
     * @var Collection<array-key, OrganMember>
     */
    #[OneToMany(
        targetEntity: OrganMember::class,
        mappedBy: 'member',
    )]
    protected Collection $organInstallations;

    /**
     * Board memberships.
     *
     * @var Collection<array-key, BoardMember>
     */
    #[OneToMany(
        targetEntity: BoardMember::class,
        mappedBy: 'member',
    )]
    protected Collection $boardInstallations;

    /**
     * Keyholdership.
     *
     * @var Collection<array-key, Keyholder>
     */
    #[OneToMany(
        targetEntity: Keyholder::class,
        mappedBy: 'member',
    )]
    protected Collection $keyGrantings;

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
        $this->organInstallations = new ArrayCollection();
        $this->boardInstallations = new ArrayCollection();
        $this->keyGrantings = new ArrayCollection();
        $this->mailingListMemberships = new ArrayCollection();
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
     * Get the birth date.
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
     * Get the organ installations.
     *
     * @return Collection<array-key, OrganMember>
     */
    public function getOrganInstallations(): Collection
    {
        return $this->organInstallations;
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
     * Member is at least 16 years old on the given date.
     */
    public function hasReached16(DateTime $onDate = new DateTime()): bool
    {
        return $this->isOlderThan($onDate, 16);
    }

    /**
     * Member is at least 18 years old on the given date.
     */
    public function hasReached18(DateTime $onDate = new DateTime()): bool
    {
        return $this->isOlderThan($onDate, 18);
    }

    /**
     * Member is at least 21 years old on the given date.
     */
    public function hasReached21(DateTime $onDate = new DateTime()): bool
    {
        return $this->isOlderThan($onDate, 21);
    }

    private function isOlderThan(
        DateTime $onDate,
        int $years,
    ): bool {
        return $onDate->diff($this->getBirth())->y >= $years;
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
     *     birthdate: string,
     *     is_16_plus: bool,
     *     is_18_plus: bool,
     *     is_21_plus: bool,
     *     membershipEndsOn: ?string,
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
            'birthdate' => $this->getBirth()->format(DateTimeInterface::ATOM),
            'is_16_plus' => $this->hasReached16(),
            'is_18_plus' => $this->hasReached18(),
            'is_21_plus' => $this->hasReached21(),
            'membershipEndsOn' => $this->getMembershipEndsOn()?->format(DateTimeInterface::ATOM) ?? null,
            'expiration' => $this->getExpiration()->format(DateTimeInterface::ATOM),
            'authenticationKey' => $this->getAuthenticationKey(),
        ];
    }

    /**
     * Get array of member for use in API endpoints
     * hides nonrelevant information by default
     *
     * @param array<array-key,string> $include
     *
     * @return array{
     *     lidnr: int,
     *     email?: ?string,
     *     full_name: string,
     *     family_name: string,
     *     middle_name: string,
     *     initials: string,
     *     given_name: string,
     *     generation: int,
     *     hidden: bool,
     *     deleted: bool,
     *     birthdate?: string,
     *     is_16_plus?: bool,
     *     is_18_plus?: bool,
     *     is_21_plus?: bool,
     *     expiration: string,
     *     organs?: array<array-key, array{
     *         organ: array{
     *             id: int,
     *             abbreviation: string,
     *         },
     *         function: string,
     *         installDate: string,
     *         dischargeDate: ?string,
     *         current: bool,
     *      }>,
     *      keyholder?: bool,
     *      membership_type?: string,
     * }
     */
    public function toArrayApi(array $include = []): array
    {
        $result = [
            'lidnr' => $this->getLidnr(),
            'full_name' => $this->getFullName(),
            'family_name' => $this->getLastName(),
            'middle_name' => $this->getMiddleName(),
            'initials' => $this->getInitials(),
            'given_name' => $this->getFirstName(),
            'generation' => $this->getGeneration(),
            'hidden' => $this->getHidden(),
            'deleted' => $this->getDeleted(),
            'expiration' => $this->getExpiration()->format(DateTimeInterface::ATOM),
        ];

        if (in_array('organs', $include)) {
            $result['organs'] = array_values(
                array_map(
                    static function (OrganMember $i) {
                        return $i->toArray();
                    },
                    array_filter(
                        $this->getOrganInstallations()->toArray(),
                        static function (OrganMember $i) {
                            return $i->isCurrent();
                        },
                    ),
                ),
            );
        }

        if (in_array('email', $include)) {
            $result['email'] = $this->getEmail();
        }

        if (in_array('birthdate', $include)) {
            $result['birthdate'] = $this->getBirth()->format(DateTimeInterface::ATOM);
        }

        if (in_array('is_16_plus', $include)) {
            $result['is_16_plus'] = $this->hasReached16();
        }

        if (in_array('is_18_plus', $include)) {
            $result['is_18_plus'] = $this->hasReached18();
        }

        if (in_array('is_21_plus', $include)) {
            $result['is_21_plus'] = $this->hasReached21();
        }

        if (in_array('keyholder', $include)) {
            $result['keyholder'] = $this->isKeyholder();
        }

        if (in_array('type', $include)) {
            $result['membership_type'] = $this->getType()->value;
        }

        return $result;
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
     * Clear all addresses.
     */
    public function clearAddresses(): void
    {
        $this->addresses = new ArrayCollection();
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
     * Is currently a keyholder.
     */
    public function isKeyholder(): bool
    {
        return array_reduce(
            $this->keyGrantings->toArray(),
            static function ($c, $kg) {
                return $c || $kg->isCurrent();
            },
            false,
        );
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
}
