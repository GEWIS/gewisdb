<?php

declare(strict_types=1);

namespace App\Twig\Components\Concerns;

use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;

/**
 * Picking a filter from a row of pills.
 *
 * `data-model` binds to the change event of a form control, and a pill is a button: it never fires one, so the
 * binding never fires either and the pills sit there doing nothing. They call this action instead, which is also how
 * GEWISWEB's overviews drive theirs.
 *
 * The class using this declares `public string $filter` as a writable `#[LiveProp]` and an `onFilterUpdated()` that
 * drops whatever it cached.
 */
trait FilterPillsTrait
{
    #[LiveAction]
    public function filterBy(#[LiveArg]
        string $filter,): void
    {
        $this->filter = $filter;
        $this->onFilterUpdated();
    }
}
