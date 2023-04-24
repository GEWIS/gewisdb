<?php

declare(strict_types=1);

namespace Checker\Model\Error;

use Checker\Model\Error;
use Database\Model\SubDecision\Key\Granting as KeyGrantingModel;

/**
 * Error for when a key code is granted with `until` past September 1st of the next association year.
 *
 * @extends Error<KeyGrantingModel>
 */
class KeyGrantedPastBoundary extends Error
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
            'Key code granted to %s has an expiration of %s, this is after September 1st of the next association year.',
            $this->getGranting()->getGrantee()->getFullName(),
            $this->getGranting()->getUntil()->format('Y-m-d'),
        );
    }
}
