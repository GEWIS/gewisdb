<?php

declare(strict_types=1);

namespace App\ViewModel\Database;

use DateTime;

/**
 * One decision in the decision list, with its content already escaped for LaTeX.
 */
final readonly class ExportedDecision
{
    public function __construct(
        public string $hash,
        public DateTime $date,
        public string $content,
    ) {
    }
}
