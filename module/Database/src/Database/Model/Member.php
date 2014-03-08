<?php

namespace Database\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * Member model.
 *
 * @ORM\Entity
 */
class Member
{

    /**
     * The user
     *
     * @ORM\Id
     * @ORM\Column(type="integer", name="lidnr")
     * @ORM\OneToOne(targetEntity="User\Model\User")
     * @ORM\JoinColumn(name="lidnr", referencedColumnName="lidnr")
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
     * Sex of the member.
     *
     * Either one of:
     * - M
     * - F
     *
     * @todo Create constants and enforce in setter
     *
     * @ORM\Column(type="string")
     */
    protected $sex;

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
     * Member type.
     *
     * This can be one of the following, as defined by the GEWIS statuten:
     *
     * - ordinary
     * - prolonged
     * - external
     * - extraordinary
     * - honorary
     *
     * You can find the GEWIS Statuten here:
     *
     * http://gewis.nl/vereniging/statuten/statuten.php
     *
     * Zie artikel 7 lid 1 en 2.
     *
     * @todo Create constants and enforce in setter
     *
     * @ORM\Column(type="string")
     */
    protected $type;

    /**
     * Expiration date of membership.
     *
     * @ORM\Column(type="date")
     */
    protected $expiration;

    /**
     * Member birth date.
     *
     * @ORM\Column(type="date")
     */
    protected $birth;

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
     * Get the member's sex.
     *
     * @return string
     */
    public function getSex()
    {
        return $this->sex;
    }

    /**
     * Set the member's sex.
     *
     * @todo enforce correct values
     *
     * @param string $sex
     */
    public function setSex($sex)
    {
        $this->sex = $sex;
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
     * @todo Enforce this
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Get the expiration date.
     *
     * @return \DateTime
     */
    public function getExpiration()
    {
        return $this->expiration;
    }

    /**
     * Set the expiration date.
     *
     * @param \DateTime $date
     */
    public function setDate(\DateTime $date)
    {
        $this->date = $date;
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
}
