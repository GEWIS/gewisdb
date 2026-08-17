<?php

declare(strict_types=1);

namespace App\Entity\Report\SubDecision;

use App\Entity\Report\SubDecision;
use Doctrine\ORM\Mapping\Entity;

/**
 * Entity for undefined decisions.
 */
#[Entity]
class Other extends SubDecision
{
}
