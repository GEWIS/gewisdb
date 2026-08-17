<?php

declare(strict_types=1);

namespace App\Entity\Report\SubDecision;

use App\Entity\Report\SubDecision;
use App\Repository\Report\SubDecision\OtherRepository;
use Doctrine\ORM\Mapping\Entity;

/**
 * Entity for undefined decisions.
 */
#[Entity(repositoryClass: OtherRepository::class)]
class Other extends SubDecision
{
}
