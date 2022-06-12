<?php

namespace Checker\Model;

/**
 * Class Error
 *
 * Denotes an error that was occured while checking a database
 * i.e. the database is left in a wrong state
 *
 * @package Checker\Model
 */
abstract class Error
{
    /**
     * @var \Database\Model\Meeting Meeting for which the error is detected
     */
    protected $meeting;

    public function getMeeting()
    {
        return $this->meeting;
    }

    /**
     * @var \Database\Model\SubDecision Decision that caused the error
     * Note that this does not necessarily have to made made during $meeting
     */
    protected $subDecision;

    public function getSubDecision()
    {
        return $this->subDecision;
    }

    /**
     * Create a new desciption
     *
     * @param \Database\Model\Meeting $meeting Meeting for which the error is detected
     * @param \Database\Model\SubDecision $subDecision DEcision that caused the error
     */
    public function __construct(
        \Database\Model\Meeting $meeting,
        \Database\Model\SubDecision $subDecision
    ) {
        $this->meeting = $meeting;
        $this->subDecision = $subDecision;
    }


    /**
     * Return a textual representation of the Error
     */
    abstract public function asText();
}
