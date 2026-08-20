<?php

declare(strict_types=1);

namespace App\Entity\Report\SubDecision\Financial;

use App\Repository\Report\SubDecision\Financial\StatementRepository;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: StatementRepository::class)]
class Statement extends Budget
{
}
