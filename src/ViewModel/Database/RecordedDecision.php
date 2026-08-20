<?php

declare(strict_types=1);

namespace App\ViewModel\Database;

/**
 * A decision that has just been recorded, as it is shown back to whoever entered it.
 *
 * It reads in every language the application has, so that a mistake in either wording is caught while the meeting is
 * still being minuted.
 */
final readonly class RecordedDecision
{
    /**
     * @param string[] $contents the decision, one entry per language
     * @param string[] $warnings what is worth pointing out about the decision, but did not stand in its way
     */
    public function __construct(
        public string $hash,
        public string $meetingType,
        public int $meetingNumber,
        public string $copyContent,
        public array $contents,
        public array $warnings,
    ) {
    }
}
