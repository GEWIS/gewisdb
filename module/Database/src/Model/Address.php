<?php

namespace Database\Model;

use Application\Model\Enums\{
    AddressTypes,
    PostalRegions,
};
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
    Id,
    JoinColumn,
    ManyToOne,
};

/**
 * Address model.
 */
#[Entity]
class Address
{
    /**
     * Member.
     */
    #[Id]
    #[ManyToOne(
        targetEntity: Member::class,
        inversedBy: "addresses",
    )]
    #[JoinColumn(
        name: "lidnr",
        referencedColumnName: "lidnr",
        nullable: false,
    )]
    protected ?Member $member = null;

    /**
     * Type
     *
     * Can be one of:
     *
     * - home (Parent's home)
     * - student (Student's home)
     * - mail (Where GEWIS mail should go to)
     */
    #[Id]
    #[Column(
        type: "string",
        enumType: AddressTypes::class,
    )]
    protected AddressTypes $type;

    /**
     * Country.
     */
    #[Column(
        type: "string",
        enumType: PostalRegions::class,
    )]
    protected PostalRegions $country;

    /**
     * Street.
     */
    #[Column(type: "string")]
    protected string $street;

    /**
     * House number (+ suffix)
     */
    #[Column(type: "string")]
    protected string $number;

    /**
     * Postal code.
     */
    #[Column(type: "string")]
    protected string $postalCode;

    /**
     * City.
     */
    #[Column(type: "string")]
    protected string $city;

    /**
     * Phone number.
     */
    #[Column(type: "string")]
    protected string $phone;

    /**
     * Get the member.
     *
     * @return Member|null
     */
    public function getMember(): ?Member
    {
        return $this->member;
    }

    /**
     * Set the member.
     *
     * @param Member $member
     */
    public function setMember(Member $member): void
    {
        $this->member = $member;
    }

    /**
     * Get the type.
     *
     * @return AddressTypes
     */
    public function getType(): AddressTypes
    {
        return $this->type;
    }

    /**
     * Set the type.
     *
     * @param AddressTypes $type
     */
    public function setType(AddressTypes $type): void
    {
        $this->type = $type;
    }

    /**
     * Get the country.
     *
     * @return PostalRegions
     */
    public function getCountry(): PostalRegions
    {
        return $this->country;
    }

    /**
     * Set the country.
     *
     * @param PostalRegions $country
     */
    public function setCountry(PostalRegions $country): void
    {
        $this->country = $country;
    }

    /**
     * Get the street.
     *
     * @return string
     */
    public function getStreet(): string
    {
        return $this->street;
    }

    /**
     * Set the street.
     *
     * @param string $street
     */
    public function setStreet(string $street): void
    {
        $this->street = $street;
    }

    /**
     * Get the house number (+ suffix).
     *
     * @return string
     */
    public function getNumber(): string
    {
        return $this->number;
    }

    /**
     * Set the house number (+ suffix).
     *
     * @param string $number
     */
    public function setNumber(string $number): void
    {
        $this->number = $number;
    }

    /**
     * Set the postal code.
     *
     * @param string $postalCode
     */
    public function setPostalCode(string $postalCode): void
    {
        $this->postalCode = $postalCode;
    }

    /**
     * Get the postal code.
     *
     * @return string
     */
    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    /**
     * Get the city.
     *
     * @return string
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * Set the city.
     *
     * @param string $city
     */
    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    /**
     * Get the phone number.
     *
     * @return string
     */
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * Set the phone number.
     *
     * @param string $phone
     */
    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    /**
     * Convert to array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'type' => $this->getType(),
            'country' => $this->getCountry(),
            'street' => $this->getStreet(),
            'number' => $this->getNumber(),
            'city' => $this->getCity(),
            'postalCode' => $this->getPostalCode(),
            'phone' => $this->getPhone(),
        ];
    }
}
