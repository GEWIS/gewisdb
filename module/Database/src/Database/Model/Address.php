<?php

namespace Database\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * Address model.
 *
 * @ORM\Entity
 */
class Address
{

    /**
     * Member.
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Member")
     * @ORM\JoinColumn(name="lidnr", referencedColumnName="lidnr")
     */
    protected $member;

    /**
     * Type
     *
     * Can be one of:
     *
     * - home (Parent's home)
     * - student (Student's home)
     * - mail (Where GEWIS mail should go to)
     *
     * @todo enforce this with constants and setter
     *
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    protected $type;

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
    protected $postalcode;

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
}
