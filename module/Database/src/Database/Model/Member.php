<?php

namespace Database\Model;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Member model.
 *
 * @ORM\Entity
 */
class Member
{

    const GENDER_MALE = 'm';
    const GENDER_FEMALE = 'f';
    const GENDER_OTHER = 'o';

    const TYPE_ORDINARY = 'ordinary';
    const TYPE_EXTERNAL = 'external';
    const TYPE_GRADUATE = 'graduate';
    const TYPE_HONORARY = 'honorary';

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
     * @ORM\Column(type="string",length=1)
     */
    protected $gender;

    /**
     * Generation.
     *
     * This is the year that this member became a GEWIS member. This is not
     * a academic year, but rather a calendar year.
     *
     * @ORM\Column(type="integer")
     */
    protected $generation;

    /**
     * TU/e registration number.
     *
     * @ORM\Column(type="integer",nullable=true)
     */
    protected $tuenumber;

    /**
     * Study of the member.
     *
     * @ORM\Column(type="string",nullable=true)
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
     * Date when the real membership ("ordinary" or "external") of the member will have ended, in other words, from this
     * date onwards they are "graduate". If `null`, the expiration is rolling and will be the end of the current
     * association year.
     *
     * @ORM\Column(type="date", nullable=true)
     */
    protected $membershipEndsOn = null;

    /**
     * Last date membership status was checked.
     *
     * @ORM\Column(type="date", nullable=true)
     */
    protected $lastCheckedOn = null;

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
     * @ORM\Column(type="string",nullable=true)
     */
    protected $iban;

    /**
     * If the member receives a 'supremum'
     *
     * @ORM\Column(type="string",nullable=true)
     */
    protected $supremum;

    /**
     * Addresses of this member.
     *
     * @ORM\OneToMany(targetEntity="Address", mappedBy="member",cascade={"persist","remove"})
     */
    protected $addresses;

    /**
     * Installations of this member.
     *
     * @ORM\OneToMany(targetEntity="Database\Model\SubDecision\Installation",mappedBy="member")
     */
    protected $installations;

    /**
     * Memberships of mailing lists.
     *
     * @ORM\ManyToMany(targetEntity="MailingList", inversedBy="members")
     * @ORM\JoinTable(name="members_mailinglists",
     *      joinColumns={@ORM\JoinColumn(name="lidnr", referencedColumnName="lidnr")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="name", referencedColumnName="name")}
     * )
     */
    protected $lists;

    /**
     * Static method to get available genders.
     *
     * @return array
     */
    protected static function getGenders()
    {
        return array(
            self::GENDER_MALE,
            self::GENDER_FEMALE,
            self::GENDER_OTHER
        );
    }

    /**
     * Static method to get available member types.
     *
     * @return array
     */
    protected static function getTypes()
    {
        return array(
            self::TYPE_ORDINARY,
            self::TYPE_EXTERNAL,
            self::TYPE_GRADUATE,
            self::TYPE_HONORARY
        );
    }


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
     * Get the generation.
     *
     * @return string
     */
    public function getGeneration()
    {
        return $this->generation;
    }

    /**
     * Set the generation.
     *
     * @param string $generation
     */
    public function setGeneration($generation)
    {
        $this->generation = $generation;
    }

    /**
     * Get the TU/e registration number.
     *
     * @return int
     */
    public function getTuenumber()
    {
        return $this->tuenumber;
    }

    /**
     * Set the TU/e registration number.
     *
     * @param int $tuenumber
     */
    public function setTuenumber($tuenumber)
    {
        $this->tuenumber = $tuenumber;
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
     * Get the date on which the member's membership will expire and has to be renewed.
     *
     * The information comes from the statuten and HR.
     *
     * @return \DateTime|null
     * @throws \Exception
     */
    public function getExpiration()
    {
        // At the end of the current association year (unless...).
        $exp = new \DateTime();
        $exp->setTime(0, 0);

        if ($exp->format('m') >= 7) {
            $year = (int) $exp->format('Y') + 1;
        } else {
            $year = $exp->format('Y');
        }

        switch ($this->getType()) {
            case self::TYPE_ORDINARY:
            case self::TYPE_EXTERNAL:
            case self::TYPE_GRADUATE:
                // If the membership ends within this association year, set expiration to an additional year. This
                // accounts for expiration of their original membership and their new one.
                if (null !== ($membershipEndsOn = $this->getMembershipEndsOn())) {
                    if ($membershipEndsOn->format('Y') >= $year) {
                        $year += 1;
                    }
                }

                break;
            case self::TYPE_HONORARY:
                // infinity (1000 is close enough, right?)
                $year += 1000;
                break;
        }

        // At the end of the current association year.
        $exp->setDate($year, 7, 1);

        return $exp;
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
     * Get the date on which the membership of the member will have ended (i.e., they have become "graduate").
     *
     * @return \DateTime|null
     */
    public function getMembershipEndsOn()
    {
        return $this->membershipEndsOn;
    }

    /**
     * Set the date on which the membership of the member will have ended (i.e., they have become "graduate").
     *
     * @param \DateTime|null $membershipEndsOn
     */
    public function setMembershipEndsOn($membershipEndsOn)
    {
        $this->membershipEndsOn = $membershipEndsOn;
    }

    /**
     * Get the date of when the membership status was last checked.
     *
     * @return \DateTime
     */
    public function getLastCheckedOn()
    {
        return $this->lastCheckedOn;
    }

    /**
     * Set the date of when the membership status was last checked.
     *
     * @param \DateTime $lastCheckedOn
     */
    public function setLastCheckedOn($lastCheckedOn)
    {
        $this->lastCheckedOn = $lastCheckedOn;
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
     * Get the installations.
     *
     * @return ArrayCollection
     */
    public function getInstallations()
    {
        return $this->installations;
    }

    /**
     * Convert to array.
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'lidnr' => $this->getLidnr(),
            'email' => $this->getEmail(),
            'fullName' => $this->getFullName(),
            'lastName' => $this->getLastName(),
            'middleName' => $this->getMiddleName(),
            'initials' => $this->getInitials(),
            'firstName' => $this->getFirstName(),
            'generation' => $this->getGeneration(),
            'expiration' => $this->getExpiration()->format('l j F Y')
        );
    }

    /**
     * Get all addresses.
     *
     * @return ArrayCollection all addresses
     */
    public function getAddresses()
    {
        return $this->addresses;
    }

    /**
     * Clear all addresses.
     */
    public function clearAddresses()
    {
        $this->addresses = new ArrayCollection();
    }

    /**
     * Add multiple addresses.
     *
     * @param array $addresses
     */
    public function addAddresses($addresses) {
        foreach ($addresses as $address) {
            $this->addAddress($address);
        }
    }

    /**
     * Add an address.
     *
     * @param Address $address
     */
    public function addAddress(Address $address)
    {
        $address->setMember($this);
        $this->addresses[] = $address;
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
        $list->addMember($this);
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

    /**
     * Set the home address.
     *
     * @param Address $address
     */
    public function setHomeAddress(Address $address)
    {
        $this->addAddress($address);
    }

    /**
     * Set the student address.
     *
     * @param Address $address
     */
    public function setStudentAddress(Address $address)
    {
        $this->addAddress($address);
    }
}
