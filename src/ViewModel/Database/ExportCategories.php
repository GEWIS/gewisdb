<?php

declare(strict_types=1);

namespace App\ViewModel\Database;

/**
 * The decision list, in the three sections it is published in.
 */
final readonly class ExportCategories
{
    /**
     * @param ExportedDecision[] $financial budgets and statements
     * @param ExportedDecision[] $install   everything that founds an organ or changes who is in it
     * @param ExportedDecision[] $other
     */
    public function __construct(
        public array $financial,
        public array $install,
        public array $other,
    ) {
    }
}
