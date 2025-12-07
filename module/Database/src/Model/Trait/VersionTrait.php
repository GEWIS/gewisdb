<?php

declare(strict_types=1);

namespace Database\Model\Trait;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Version;

trait VersionTrait
{
    /**
     * integer version
     * From the docs:
     * "Version numbers [should] be preferred as they can not potentially conflict in a highly concurrent environment"
     */
    #[Version()]
    #[Column(type: 'integer')]
    private int $version;
}
