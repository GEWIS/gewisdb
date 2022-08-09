<?php

namespace Checker\Model;

use Database\Model\Meeting as MeetingModel;
use Database\Model\SubDecision as SubDecisionModel;

/**
 * Class Error
 *
 * Denotes an error that was occurred while checking a database
 * i.e. the database is left in a wrong state
 */
abstract class Error
{
    /**
     * Meeting for which the error is detected.
     */
    protected MeetingModel $meeting;

    /**
     * Note that this does not necessarily have to be made during `$meeting`.
     */
    protected SubDecisionModel $subDecision;

    /**
     * Create a new description.
     */
    public function __construct(
        MeetingModel $meeting,
        SubDecisionModel $subDecision,
    ) {
        $this->meeting = $meeting;
        $this->subDecision = $subDecision;
    }

    public function getMeeting(): MeetingModel
    {
        return $this->meeting;
    }

    public function getSubDecision(): SubDecisionModel
    {
        return $this->subDecision;
    }

    /**
     * Return a textual representation of the error.
     */
    abstract public function asText(): string;
}
