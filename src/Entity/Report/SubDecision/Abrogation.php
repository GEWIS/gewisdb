<?php

declare(strict_types=1);

namespace App\Entity\Report\SubDecision;

use App\Repository\Report\SubDecision\AbrogationRepository;
use Doctrine\ORM\Mapping\Entity;

/**
 * Abrogation of an organ.
 */
#[Entity(repositoryClass: AbrogationRepository::class)]
class Abrogation extends FoundationReference
{
}
