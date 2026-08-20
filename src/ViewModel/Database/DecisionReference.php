<?php

declare(strict_types=1);

namespace App\ViewModel\Database;

use App\Entity\Database\Decision;

/**
 * How a decision is referred to from somewhere else: the four parts of its identity, ready to be printed.
 */
final readonly class DecisionReference
{
    public function __construct(
        public string $meetingType,
        public int $meetingNumber,
        public int $point,
        public int $number,
    ) {
    }

    public static function fromDecision(Decision $decision): self
    {
        return new self(
            $decision->getMeetingType()->value,
            $decision->getMeetingNumber(),
            $decision->getPoint(),
            $decision->getNumber(),
        );
    }
}
