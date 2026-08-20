<?php

declare(strict_types=1);

namespace App\Entity\Report\SubDecision\Key;

use App\Entity\Report\Keyholder;
use App\Entity\Report\Member;
use App\Entity\Report\SubDecision;
use App\Entity\Report\Traits\MemberAwareTrait;
use App\Repository\Report\SubDecision\Key\GrantingRepository;
use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\OneToOne;

#[Entity(repositoryClass: GrantingRepository::class)]
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
     * Set the keyholder decision.
     *
     * Kept in step with the owning side, so that a keyholder only just derived from this granting can be found right
     * away, without having to go through the database for it.
     */
    public function setKeyholder(Keyholder $keyholder): void
    {
        $this->keyholder = $keyholder;
    }

    /**
     * Forget what was derived from this subdecision, because it no longer exists.
     *
     * Leaves the property uninitialised again, which is how the rest of the code recognises that there is nothing.
     */
    public function clearKeyholder(): void
    {
        unset($this->keyholder);
    }

    /**
     * Get the keyholder decision.
     */
    public function getKeyholder(): Keyholder
    {
        return $this->keyholder;
    }
}
