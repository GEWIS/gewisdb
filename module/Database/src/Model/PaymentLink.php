<?php

declare(strict_types=1);

namespace Database\Model;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;

/**
 * Payment link for prospective members.
 */
#[Entity]
class PaymentLink extends ActionLink
{
    /**
     * The prospective member
     */
    #[OneToOne(
        targetEntity: ProspectiveMember::class,
        inversedBy: 'paymentLink',
    )]
    #[JoinColumn(
        name: 'prospective_member',
        referencedColumnName: 'lidnr',
        onDelete: 'cascade',
    )]
    private ProspectiveMember $prospectiveMember;

    public function getProspectiveMember(): ProspectiveMember
    {
        return $this->prospectiveMember;
    }

    public function setProspectiveMember(ProspectiveMember $prospectiveMember): void
    {
        $this->prospectiveMember = $prospectiveMember;
    }
}
