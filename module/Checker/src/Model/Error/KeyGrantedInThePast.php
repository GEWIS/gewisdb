<?php

declare(strict_types=1);

namespace Checker\Model\Error;

use Checker\Model\Error;
use Database\Model\SubDecision\Key\Granting as KeyGrantingModel;

use function sprintf;

/**
 * Error for when a key code is granted with a negative duration (i.e., its `until` is in the past).
 *
 * @extends Error<KeyGrantingModel>
 */
class KeyGrantedInThePast extends Error
{
    public function __construct(KeyGrantingModel $granting)
    {
        parent::__construct(
            $granting->getDecision()->getMeeting(),
            $granting,
        );
    }

    /**
     * Get the granting.
     */
    private function getGranting(): KeyGrantingModel
    {
        return $this->getSubDecision();
    }

    public function asText(): string
    {
        return sprintf(
            'Key code granted to %s has an expiration of %s, this is before %s.',
            $this->getGranting()->getGrantee()->getFullName(),
            $this->getGranting()->getUntil()->format('Y-m-d'),
            $this->getMeeting()->getDate()->format('Y-m-d'),
        );
    }
}
