<?php

declare(strict_types=1);

namespace Report\Model\SubDecision;

use Doctrine\ORM\Mapping\Entity;
use Report\Model\SubDecision;

/**
 * Entity for undefined decisions.
 */
#[Entity]
class Other extends SubDecision
{
}
