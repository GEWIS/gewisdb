<?php

declare(strict_types=1);

namespace CheckerTest\Model\Error;

use Checker\Model\Error\OrganMeetingType;
use CheckerTest\Model\Error;
use Override;

class OrganMeetingTypeTest extends Error
{
    #[Override]
    protected function create(): OrganMeetingType
    {
        $foundation = $this->getFoundation();

        return new OrganMeetingType($foundation);
    }
}
