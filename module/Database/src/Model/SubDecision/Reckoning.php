<?php

declare(strict_types=1);

namespace Database\Model\SubDecision;

use Doctrine\ORM\Mapping\Entity;

/**
 *
 */
#[Entity]
class Reckoning extends Budget
{
    /**
     * Decision template
     *
     * @return string
     */
    protected function getTemplate(): string
    {
        return 'De afrekening %NAME% van %AUTHOR%, versie %VERSION% van %DATE% wordt %APPROVAL%%CHANGES%.';
    }
}
