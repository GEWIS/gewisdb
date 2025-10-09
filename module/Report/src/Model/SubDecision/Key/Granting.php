<?php

declare(strict_types=1);

namespace Report\Model\SubDecision\Key;

use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\OneToOne;
use Report\Model\Keyholder;
use Report\Model\Member;
use Report\Model\SubDecision;
use Report\Model\Trait\MemberAwareTrait;

#[Entity]
class Granting extends SubDecision
{
    use MemberAwareTrait;

    /**
     * Till when the keycode is granted.
     */
    #[Column(type: 'date')]
    private DateTime $until;

    /**
     * Discharges.
     */
    #[OneToOne(
        targetEntity: Withdrawal::class,
        mappedBy: 'granting',
    )]
    private ?Withdrawal $withdrawal = null;

    /**
     * Keyholder reference.
     */
    #[OneToOne(
        targetEntity: Keyholder::class,
        mappedBy: 'grantingDec',
    )]
    private Keyholder $keyholder;

    /**
     * Get the member.
     *
     * @psalm-suppress InvalidNullableReturnType
     */
    public function getMember(): Member
    {
        return $this->member;
    }

    /**
     * Get the date.
     */
    public function getUntil(): DateTime
    {
        return $this->until;
    }

    /**
     * Set the date.
     */
    public function setUntil(DateTime $until): void
    {
        $this->until = $until;
    }

    /**
     * Get the withdrawal decision.
     */
    public function getWithdrawal(): ?Withdrawal
    {
        return $this->withdrawal;
    }

    /**
     * Clears the withdrawal, if it exists.
     */
    public function clearWithdrawal(): void
    {
        $this->withdrawal = null;
    }

    /**
     * Get the keyholder decision.
     */
    public function getKeyholder(): Keyholder
    {
        return $this->keyholder;
    }
}
