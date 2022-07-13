<?php

namespace CheckerTest\Model\Error;

use Checker\Model\Error\OrganMeetingType;

class OrganMeetingTypeTest extends \CheckerTest\Model\Error
{
    protected function create()
    {
        $foundation = $this->getFoundation();
        return new OrganMeetingType($foundation);
    }
}
