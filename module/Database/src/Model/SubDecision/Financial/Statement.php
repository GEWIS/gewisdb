<?php

declare(strict_types=1);

namespace Database\Model\SubDecision\Financial;

use Doctrine\ORM\Mapping\Entity;

#[Entity]
class Statement extends Budget
{
    protected function getTemplate(): string
    {
        return 'De afrekening %NAME% van %AUTHOR%, versie %VERSION% van %DATE% wordt %APPROVAL%%CHANGES%.';
    }

    protected function getAlternativeTemplate(): string
    {
        return 'The financial statement %NAME% by %AUTHOR%, version %VERSION% dated %DATE% is %APPROVAL%%CHANGES%.';
    }
}
