<?php

declare(strict_types=1);

namespace App\Entity\Report\SubDecision;

use App\Entity\Database\Enums\InstallationFunctions;
use App\Entity\Report\Member;
use App\Entity\Report\OrganMember;
use App\Entity\Report\Traits\MemberAwareTrait;
use App\Repository\Report\SubDecision\InstallationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\AssociationOverride;
use Doctrine\ORM\Mapping\AssociationOverrides;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * Installation into organ.
 */
#[Entity(repositoryClass: InstallationRepository::class)]
#[AssociationOverrides([
    new AssociationOverride(
        name: 'member',
        inversedBy: 'installations',
    ),
])]
class Installation extends FoundationReference
{
    use MemberAwareTrait;

    /**
     * Function given.
     */
    #[Column(
        enumType: InstallationFunctions::class,
    )]
    private InstallationFunctions $function;

    /**
     * Reappointment subdecisions if this installation was prolonged (can be done multiple times).
     *
     * @var Collection<array-key, Reappointment>
     */
    #[OneToMany(
        targetEntity: Reappointment::class,
        mappedBy: 'installation',
    )]
    private Collection $reappointments;

    /**
     * Discharges.
     */
    #[OneToOne(
        targetEntity: Discharge::class,
        mappedBy: 'installation',
    )]
    private ?Discharge $discharge = null;

    /**
     * The organmember reference.
     */
    #[OneToOne(
        targetEntity: OrganMember::class,
        mappedBy: 'installation',
    )]
    private OrganMember $organMember;

    public function __construct()
    {
        $this->reappointments = new ArrayCollection();
    }

    /**
     * Get the function.
     */
    public function getFunction(): InstallationFunctions
    {
        return $this->function;
    }

    /**
     * Set the function.
     */
    public function setFunction(InstallationFunctions $function): void
    {
        $this->function = $function;
    }

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
     * Set the member.
     */
    public function setMember(Member $member): void
    {
        $this->member = $member;
    }

    /**
     * Get the reappointments, if they exist.
     *
     * @return Collection<array-key, Reappointment>
     */
    public function getReappointments(): Collection
    {
        return $this->reappointments;
    }

    /**
     * Removes the reappointments, if they exist.
     */
    public function removeReappointment(Reappointment $reappointment): void
    {
        if (!$this->reappointments->contains($reappointment)) {
            return;
        }

        $this->reappointments->removeElement($reappointment);
    }

    /**
     * Get the discharge, if it exists.
     */
    public function getDischarge(): ?Discharge
    {
        return $this->discharge;
    }

    /**
     * Clears the discharge, if it exists.
     */
    public function clearDischarge(): void
    {
        $this->discharge = null;
    }

    /**
     * Set the organ member reference.
     *
     * Kept in step with the owning side, so that a member only just derived from this installation can be found right
     * away, without having to go through the database for it.
     */
    public function setOrganMember(OrganMember $organMember): void
    {
        $this->organMember = $organMember;
    }

    /**
     * Forget what was derived from this subdecision, because it no longer exists.
     *
     * Leaves the property uninitialised again, which is how the rest of the code recognises that there is nothing.
     */
    public function clearOrganMember(): void
    {
        unset($this->organMember);
    }

    /**
     * Get the organ member reference.
     */
    public function getOrganMember(): OrganMember
    {
        return $this->organMember;
    }
}
