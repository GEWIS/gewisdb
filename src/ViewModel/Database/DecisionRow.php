<?php

declare(strict_types=1);

namespace App\ViewModel\Database;

/**
 * One decision as it reads in a meeting's decision list.
 *
 * The sub-decisions arrive as the sentences they read as, because assembling those needs a translator that a template
 * cannot hand the entity.
 */
final readonly class DecisionRow
{
    /**
     * @param string[] $subdecisions
     */
    public function __construct(
        public int $point,
        public int $number,
        public array $subdecisions,
        public string $copyContent,
        public ?DecisionReference $annulment,
    ) {
    }
}
