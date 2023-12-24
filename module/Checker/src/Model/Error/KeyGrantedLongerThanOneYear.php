<?php

declare(strict_types=1);

namespace Checker\Model\Error;

use Checker\Model\Error;
use Database\Model\SubDecision\Key\Granting as KeyGrantingModel;

use function sprintf;

/**
 * Error for when a key code is granted for a period longer than a year.
 *
 * @extends Error<KeyGrantingModel>
 */
class KeyGrantedLongerThanOneYear extends Error
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
            'Key code granted to %s has an expiration of %s, this is longer than the 1 year.',
            $this->getGranting()->getMember()->getFullName(),
            $this->getGranting()->getUntil()->format('Y-m-d'),
        );
    }
}
