<?php

namespace Database\Model;
use Doctrine\ORM\Mapping as ORM;
/**
 * Model for pending member updates
 *
 * @ORM\Entity
 */
class MemberUpdate
{

    /**
     * The member id
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $lidnr;

    /**
     * Member's email address.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $email;

    /**
     * Member's last name.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $lastName;

    /**
     * Middle name.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $middleName;

    /**
     * Initials.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $initials;

    /**
     * First name.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $firstName;

    /**
     * Generation.
     *
     * This is the year that this member became a GEWIS member. This is not
     * a academic year, but rather a calendar year.
     *
     * @ORM\Column(type="integer", nullable=true)
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
     * Member birth date.
     *
     * @ORM\Column(type="date", nullable=true)
     */
    protected $birth;

    /**
     * Create the member update from an update request
     *
     * @param integer $lidnr
     * @param array $data
     */
    public function loadData($lidnr, $data)
    {
        $this->lidnr = $lidnr;
        foreach ($data as $key => $value)
        {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
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
