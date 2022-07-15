<?php

namespace Database\Model;

use Application\Model\Enums\{
    AddressTypes,
    GenderTypes,
};
use DateTime;
use DateTimeImmutable;
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
};
use Doctrine\Common\Collections\Collection;

/**
 * ProspectiveMember model.
 */
#[Entity]
class ProspectiveMember
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
    #[Column(type: "string")]
    protected string $email = '';

    /**
     * Member's last name.
     */
    #[Column(type: "string")]
    protected string $lastName = '';

    /**
     * Middle name.
     */
    #[Column(type: "string")]
    protected string $middleName = '';

    /**
     * Initials.
     */
    #[Column(type: "string")]
    protected string $initials = '';

    /**
     * First name.
     */
    #[Column(type: "string")]
    protected string $firstName = '';

    /**
     * Gender of the member.
     *
     * Either one of:
     * - m
     * - f
     */
    #[Column(
        type: "string",
        enumType: GenderTypes::class,
    )]
    protected GenderTypes $gender = GenderTypes::Other;

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
     * Last changed date of membership.
     */
    #[Column(type: "date")]
    protected DateTime $changedOn;

    /**
     * Member birthdate.
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
     * Country.
     *
     * By default, netherlands.
     */
    #[Column(type: "string")]
    protected string $country = 'netherlands';

    /**
     * Street.
     */
    #[Column(type: "string")]
    protected string $street = '';

    /**
     * House number (+ suffix)
     */
    #[Column(type: "string")]
    protected string $number = '';

    /**
     * Postal code.
     */
    #[Column(type: "string")]
    protected string $postalCode = '';

    /**
     * City.
     */
    #[Column(type: "string")]
    protected string $city = '';

    /**
     * Phone number.
     */
    #[Column(type: "string")]
    protected string $phone = '';

    /**
     * Memberships of mailing lists.
     */
    #[ManyToMany(
        targetEntity: MailingList::class,
        inversedBy: "members",
    )]
    #[JoinTable(name: "prospective_members_mailinglists")]
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
     * The signature image URL.
     */
    #[Column(
        type: "string",
        nullable: true,
    )]
    protected ?string $signature = null;

    /**
     * The signature location
     */
    #[Column(
        type: "string",
        nullable: true,
    )]
    protected ?string $signatureLocation = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->birth = new DateTime();
        $this->changedOn = new DateTime();

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
     * @return string
     */
    public function getEmail(): string
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
     * @param string $lidnr
     */
    public function setLidnr(string $lidnr): void
    {
        $this->lidnr = $lidnr;
    }

    /**
     * Set the member's email address.
     *
     * @param string $email
     */
    public function setEmail(string $email): void
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
     * Get the member's gender.
     *
     * @return GenderTypes
     */
    public function getGender(): GenderTypes
    {
        return $this->gender;
    }

    /**
     * Set the member's gender.
     *
     * @param GenderTypes $gender
     */
    public function setGender(GenderTypes $gender): void
    {
        $this->gender = $gender;
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
     * @param string $study
     */
    public function setStudy(string $study): void
    {
        $this->study = $study;
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
    public function getIban(): ?string
    {
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
     * Get the signature image URL.
     *
     * @return string|null
     */
    public function getSignature(): ?string
    {
        return $this->signature;
    }

    /**
     * Set the signature image URL.
     *
     * @param string|null $signature
     */
    public function setSignature(?string $signature): void
    {
        $this->signature = $signature;
    }

    /**
     * Get the signature location
     *
     * @return string|null
     */
    public function getSignatureLocation(): ?string
    {
        return $this->signature;
    }

    /**
     * Set the signature location
     *
     * @param string|null $signatureLocation
     */
    public function setSignatureLocation(?string $signatureLocation): void
    {
        $this->signatureLocation = $signatureLocation;
    }

    /**
     * Returns the characteristic of the mandate. Is unique for each form entry
     *
     * @return string
     */
    public function getMandateCharacteristic(): string
    {
        return $this->changedOn->format('Y') . "subscription" . $this->getLidnr();
    }

    /**
     * Convert to array.
     *
     * @return array
     */
    public function toArray(): array
    {
        $array = [
            'lidnr' => $this->getLidnr(),
            'email' => $this->getEmail(),
            'fullName' => $this->getFullName(),
            'lastName' => $this->getLastName(),
            'middleName' => $this->getMiddleName(),
            'initials' => $this->getInitials(),
            'firstName' => $this->getFirstName(),
            'gender' => $this->getGender(),
            'study' => $this->getStudy(),
            'birth' => $this->getBirth()->format('Y-m-d'),
            'iban' => $this->getIban(),
            'studentAddress' => $this->getAddresses()['studentAddress']->toArray(),
            'agreediban' => 1,
            'agreed' => '1',
        ];
        $array['studentAddress']['type'] = AddressTypes::Student;

        return $array;
    }

    /**
     * Get all addresses.
     *
     * @return array all addresses
     */
    public function getAddresses(): array
    {
        $address = new Address();
        $address->setType(AddressTypes::Student);
        $address->setCountry($this->country);
        $address->setStreet($this->street);
        $address->setNumber($this->number);
        $address->setPostalCode($this->postalCode);
        $address->setCity($this->city);
        $address->setPhone($this->phone);
        return [
            'studentAddress' => $address,
        ];
    }

    /**
     * Add an address.
     *
     * @param Address $address
     */
    public function setAddress(Address $address): void
    {
        $this->country = $address->getCountry();
        $this->street = $address->getStreet();
        $this->number = $address->getNumber();
        $this->postalCode = $address->getPostalCode();
        $this->city = $address->getCity();
        $this->phone = $address->getPhone();
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
}
