<?php

declare(strict_types=1);

namespace Database\Model;

use Application\Model\Enums\AddressTypes;
use Application\Model\Enums\PostalRegions;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

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
        inversedBy: 'addresses',
    )]
    #[JoinColumn(
        name: 'lidnr',
        referencedColumnName: 'lidnr',
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
        type: 'string',
        enumType: AddressTypes::class,
    )]
    protected AddressTypes $type;

    /**
     * Country.
     */
    #[Column(
        type: 'string',
        enumType: PostalRegions::class,
    )]
    protected PostalRegions $country;

    /**
     * Street.
     */
    #[Column(type: 'string')]
    protected string $street;

    /**
     * House number (+ suffix)
     */
    #[Column(type: 'string')]
    protected string $number;

    /**
     * Postal code.
     */
    #[Column(type: 'string')]
    protected string $postalCode;

    /**
     * City.
     */
    #[Column(type: 'string')]
    protected string $city;

    /**
     * Phone number.
     */
    #[Column(type: 'string')]
    protected string $phone;

    /**
     * Get the member.
     */
    public function getMember(): ?Member
    {
        return $this->member;
    }

    /**
     * Set the member.
     */
    public function setMember(Member $member): void
    {
        $this->member = $member;
    }

    /**
     * Get the type.
     */
    public function getType(): AddressTypes
    {
        return $this->type;
    }

    /**
     * Set the type.
     */
    public function setType(AddressTypes $type): void
    {
        $this->type = $type;
    }

    /**
     * Get the country.
     */
    public function getCountry(): PostalRegions
    {
        return $this->country;
    }

    /**
     * Set the country.
     */
    public function setCountry(PostalRegions $country): void
    {
        $this->country = $country;
    }

    /**
     * Get the street.
     */
    public function getStreet(): string
    {
        return $this->street;
    }

    /**
     * Set the street.
     */
    public function setStreet(string $street): void
    {
        $this->street = $street;
    }

    /**
     * Get the house number (+ suffix).
     */
    public function getNumber(): string
    {
        return $this->number;
    }

    /**
     * Set the house number (+ suffix).
     */
    public function setNumber(string $number): void
    {
        $this->number = $number;
    }

    /**
     * Set the postal code.
     */
    public function setPostalCode(string $postalCode): void
    {
        $this->postalCode = $postalCode;
    }

    /**
     * Get the postal code.
     */
    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    /**
     * Get the city.
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * Set the city.
     */
    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    /**
     * Get the phone number.
     */
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * Set the phone number.
     */
    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    /**
     * Convert to array.
     *
     * @return array{
     *     type: AddressTypes,
     *     country: PostalRegions,
     *     street: string,
     *     number: string,
     *     city: string,
     *     postalCode: string,
     *     phone: string,
     * }
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
