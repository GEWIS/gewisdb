<?php

declare(strict_types=1);

namespace Database\Model;

use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * Member update model.
 */
#[Entity]
class MemberUpdate
{
    /**
     * The member we want to update.
     */
    #[Id]
    #[OneToOne(targetEntity: Member::class)]
    #[JoinColumn(
        name: 'lidnr',
        referencedColumnName: 'lidnr',
        nullable: false,
    )]
    protected Member $member;

    /**
     * When the update was requested.
     */
    #[Column(type: 'date')]
    protected DateTime $requestedDate;

    /**
     * Member's email address.
     */
    #[Column(type: 'string')]
    protected string $email;

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
     * Get the member.
     *
     * @psalm-ignore-nullable-return
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
     * Get the member's email address.
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Set the member's email address.
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    /**
     * Get the member's last name.
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * Set the member's last name.
     */
    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    /**
     * Get the member's middle name.
     */
    public function getMiddleName(): string
    {
        return $this->middleName;
    }

    /**
     * Set the member's middle name.
     */
    public function setMiddleName(string $middleName): void
    {
        $this->middleName = $middleName;
    }

    /**
     * Get the member's initials.
     */
    public function getInitials(): string
    {
        return $this->initials;
    }

    /**
     * Set the member's initials.
     */
    public function setInitials(string $initials): void
    {
        $this->initials = $initials;
    }

    /**
     * Get the member's first name.
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * Set the member's first name.
     */
    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    /**
     * Convert most relevant items to array.
     *
     * @return array{
     *     email: string,
     *     lastName: string,
     *     middleName: string,
     *     initials: string,
     *     firstName: string,
     * }
     */
    public function toArray(): array
    {
        return [
            'email' => $this->getEmail(),
            'lastName' => $this->getLastName(),
            'middleName' => $this->getMiddleName(),
            'initials' => $this->getInitials(),
            'firstName' => $this->getFirstName(),
        ];
    }
}
