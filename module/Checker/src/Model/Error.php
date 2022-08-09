<?php

namespace Checker\Model;

use Database\Model\Meeting as MeetingModel;
use Database\Model\SubDecision as SubDecisionModel;

/**
 * Class Error
 *
 * Denotes an error that was occurred while checking a database
 * i.e. the database is left in a wrong state
 *
 * @template T
 */
abstract class Error
{
    /**
     * Create a new description.
     *
     * @param MeetingModel $meeting Meeting for which the error is detected.
     * @param T $subDecision Note that this does not necessarily have to be made during `$meeting`.
     */
    public function __construct(
        protected readonly MeetingModel $meeting,
        protected readonly SubDecisionModel $subDecision,
    ) {
    }

    public function getMeeting(): MeetingModel
    {
        return $this->meeting;
    }

    /**
     * @return T
     */
    public function getSubDecision(): SubDecisionModel
    {
        return $this->subDecision;
    }

    /**
     * Return a textual representation of the error.
     */
    abstract public function asText(): string;
}
