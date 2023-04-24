<?php

declare(strict_types=1);

namespace CheckerTest\Model\Error;

use Checker\Model\Error\OrganMeetingType;
use CheckerTest\Model\Error;

class OrganMeetingTypeTest extends Error
{
    protected function create(): OrganMeetingType
    {
        $foundation = $this->getFoundation();

        return new OrganMeetingType($foundation);
    }
}
