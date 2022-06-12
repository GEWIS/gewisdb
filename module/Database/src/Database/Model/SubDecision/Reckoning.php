<?php

namespace Database\Model\SubDecision;

use Doctrine\ORM\Mapping as ORM;
use Database\Model\SubDecision;

/**
 *
 * @ORM\Entity
 */
class Reckoning extends Budget
{
    /**
     * Decision template
     *
     * @return string
     */
    protected function getTemplate()
    {
        return 'De afrekening %NAME% van %AUTHOR%, versie %VERSION% van %DATE% wordt %APPROVAL%%CHANGES%.';
    }
}
