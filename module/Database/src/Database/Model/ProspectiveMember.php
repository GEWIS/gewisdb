<?php

namespace Database\Model;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * ProspectiveMember model.
 *
 * @ORM\Entity
 */
class ProspectiveMember
{
    public const GENDER_MALE = 'm';
    public const GENDER_FEMALE = 'f';
    public const GENDER_OTHER = 'o';

    public const TYPE_ORDINARY = 'ordinary';
    public const TYPE_EXTERNAL = 'external';
    public const TYPE_GRADUATE = 'graduate';
    public const TYPE_HONORARY = 'honorary';

    /**
     * The user
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", name="lidnr")
     */
    protected $lidnr;

    /**
     * Member's email address.
     *
     * @ORM\Column(type="string")
     */
    protected $email;

    /**
     * Member's last name.
     *
     * @ORM\Column(type="string")
     */
    protected $lastName;

    /**
     * Middle name.
     *
     * @ORM\Column(type="string")
     */
    protected $middleName;

    /**
     * Initials.
     *
     * @ORM\Column(type="string")
     */
    protected $initials;

    /**
     * First name.
     *
     * @ORM\Column(type="string")
     */
    protected $firstName;

    /**
     * Gender of the member.
     *
     * Either one of:
     * - m
     * - f
     *
     * @ORM\Column(type="string", length=1)
     */
    protected $gender;

    /**
     * TU/e username.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $tueUsername;

    /**
     * Study of the member.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $study;

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
     *
     * @ORM\Column(type="string")
     */
    protected $type;

    /**
     * Last changed date of membership.
     *
     * @ORM\Column(type="date")
     */
    protected $changedOn;

    /**
     * Member birth date.
     *
     * @ORM\Column(type="date")
     */
    protected $birth;

    /**
     * How much the member has paid for membership. 0 by default.
     *
     * @ORM\Column(type="integer")
     */
    protected $paid = 0;

    /**
     * Iban number.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $iban;

    /**
     * Country.
     *
     * By default, netherlands.
     *
     * @ORM\Column(type="string")
     */
    protected $country = 'netherlands';

    /**
     * Street.
     *
     * @ORM\Column(type="string")
     */
    protected $street;

    /**
     * House number (+ suffix)
     *
     * @ORM\Column(type="string")
     */
    protected $number;

    /**
     * Postal code.
     *
     * @ORM\Column(type="string")
     */
    protected $postalCode;

    /**
     * City.
     *
     * @ORM\Column(type="string")
     */
    protected $city;

    /**
     * Phone number.
     *
     * @ORM\Column(type="string")
     */
    protected $phone;

    /**
     * Memberships of mailing lists.
     *
     * @ORM\ManyToMany(targetEntity="MailingList", inversedBy="members")
     * @ORM\JoinTable(name="prospective_members_mailinglists",
     *     joinColumns={@ORM\JoinColumn(name="lidnr", referencedColumnName="lidnr")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="name", referencedColumnName="name")}
     * )
     */
    protected $lists;

    /**
     * The signature image URL.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $signature;

    /**
     * The signature location
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $signatureLocation;

    /**
     * Static method to get available genders.
     *
     * @return array
     */
    protected static function getGenders()
    {
        return [
            self::GENDER_MALE,
            self::GENDER_FEMALE,
            self::GENDER_OTHER
        ];
    }

    /**
     * Static method to get available member types.
     *
     * @return array
     */
    protected static function getTypes()
    {
        return [
            self::TYPE_ORDINARY,
            self::TYPE_EXTERNAL,
            self::TYPE_GRADUATE,
            self::TYPE_HONORARY
        ];
    }


    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->addresses = new ArrayCollection();
        $this->lists = new ArrayCollection();
    }

    /**
     * Get the membership number.
     *
     * @return int
     */
    public function getLidnr()
    {
        return $this->lidnr;
    }

    /**
     * Get the member's email address.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Get the member's last name.
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Get the member's middle name.
     *
     * @return string
     */
    public function getMiddleName()
    {
        return $this->middleName;
    }

    /**
     * Get the member's initials.
     *
     * @return string
     */
    public function getInitials()
    {
        return $this->initials;
    }

    /**
     * Get the member's first name.
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set the lidnr.
     *
     * @param string $lidnr
     */
    public function setLidnr($lidnr)
    {
        $this->lidnr = $lidnr;
    }

    /**
     * Set the member's email address.
     *
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * Set the member's last name.
     *
     * @param string $lastName
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    /**
     * Set the member's middle name.
     *
     * @param string $middleName
     */
    public function setMiddleName($middleName)
    {
        $this->middleName = $middleName;
    }

    /**
     * Set the member's initials.
     *
     * @param string $initals
     */
    public function setInitials($initials)
    {
        $this->initials = $initials;
    }

    /**
     * Set the member's first name.
     *
     * @param string $firstName
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    /**
     * Assemble the member's full name.
     *
     * @return string
     */
    public function getFullName()
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
     * @return string
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * Set the member's gender.
     *
     * @param string $gender
     *
     * @throws \InvalidArgumentException when the gender does not have correct value
     */
    public function setGender($gender)
    {
        if (!in_array($gender, self::getGenders())) {
            throw new \InvalidArgumentException("Invalid gender value");
        }
        $this->gender = $gender;
    }

    /**
     * Get the TU/e username.
     *
     * @return string|null
     */
    public function getTueUsername()
    {
        return $this->tueUsername;
    }

    /**
     * Set the TU/e username.
     *
     * @param string|null $tueUsername
     */
    public function setTueUsername($tueUsername)
    {
        $this->tueUsername = $tueUsername;
    }

    /**
     * Get the study.
     *
     * @return string
     */
    public function getStudy()
    {
        return $this->study;
    }

    /**
     * Set the study.
     *
     * @param string $study
     */
    public function setStudy($study)
    {
        $this->study = $study;
    }

    /**
     * Get the member type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the member type.
     *
     * @param string $type
     *
     * @throws \InvalidArgumentException When the type is incorrect.
     */
    public function setType($type)
    {
        if (!in_array($type, self::getTypes())) {
            throw new \InvalidArgumentException("Nonexisting type given.");
        }
        $this->type = $type;
    }

    /**
     * Get the birth date.
     *
     * @return \DateTime
     */
    public function getBirth()
    {
        return $this->birth;
    }

    /**
     * Set the birthdate.
     *
     * @param \DateTime $birth
     */
    public function setBirth(\DateTime $birth)
    {
        $this->birth = $birth;
    }

    /**
     * Get the date of the last membership change.
     *
     * @return \DateTime
     */
    public function getChangedOn()
    {
        return $this->changedOn;
    }

    /**
     * Set the date of the last membership change.
     *
     * @param \DateTime $changedOn
     */
    public function setChangedOn($changedOn)
    {
        $this->changedOn = $changedOn;
    }

    /**
     * Get how much has been paid.
     *
     * @return int
     */
    public function getPaid()
    {
        return $this->paid;
    }

    /**
     * Set how much has been paid.
     *
     * @param int $paid
     */
    public function setPaid($paid)
    {
        $this->paid = $paid;
    }

    /**
     * Get the IBAN.
     *
     * @return string
     */
    public function getIban()
    {
        return $this->iban;
    }

    /**
     * Set the IBAN.
     *
     * @param string $iban
     */
    public function setIban($iban)
    {
        $this->iban = $iban;
    }

    /**
     * Get if the member wants a supremum.
     *
     * @return string
     */
    public function getSupremum()
    {
        return $this->supremum;
    }

    /**
     * Set if the member wants a supremum.
     *
     * @param string $supremum
     */
    public function setSupremum($supremum)
    {
        $this->supremum = $supremum;
    }

    /**
     * Get the signature image URL.
     *
     * @return string
     */
    public function getSignature()
    {
        return $this->signature;
    }

    /**
     * Set the signature image URL.
     *
     * @param string $signature
     */
    public function setSignature($signature)
    {
        $this->signature = $signature;
    }

    /**
     * Get the signature location
     *
     * @return string
     */
    public function getSignatureLocation()
    {
        return $this->signature;
    }

    /**
     * Set the signature location
     *
     * @param string $signatureLocation
     */
    public function setSignatureLocation($signatureLocation)
    {
        $this->signatureLocation = $signatureLocation;
    }

    /**
     * Returns the characteristic of the mandate. Is unique for each form entry
     *
     * @return string
     */
    public function getMandateCharacteristic()
    {
        return $this->changedOn->format('Y') . "subscription" . $this->getLidnr();
    }

    /**
     * Convert to array.
     *
     * @return array
     */
    public function toArray()
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
            'agreed' => '1'
        ];
        $array['studentAddress']['type'] = Address::TYPE_STUDENT;

        return $array;
    }

    /**
     * Get all addresses.
     *
     * @return array all addresses
     */
    public function getAddresses()
    {
        $address = new Address();
        $address->setType(Address::TYPE_STUDENT);
        $address->setCountry($this->country);
        $address->setStreet($this->street);
        $address->setNumber($this->number);
        $address->setPostalCode($this->postalCode);
        $address->setCity($this->city);
        $address->setPhone($this->phone);
        return [
            'studentAddress' => $address
        ];
    }

    /**
     * Add an address.
     *
     * @param Address $address
     */
    public function setAddress(Address $address)
    {
        $this->addresses[] = $address;
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
     * @return ArrayCollection
     */
    public function getLists()
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
    public function addList(MailingList $list)
    {
        $this->lists[] = $list;
    }

    /**
     * Add multiple mailing lists.
     *
     * @param array $lists
     */
    public function addLists($lists)
    {
        foreach ($lists as $list) {
            $this->addList($list);
        }
    }

    /**
     * Clear the lists.
     */
    public function clearLists()
    {
        $this->lists = new ArrayCollection();
    }
}
