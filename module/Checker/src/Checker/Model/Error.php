<?php
namespace Checker\Model;

use Database\Model\Meeting as MeetingModel;
use Database\Model\SubDecision as SubDecisionModel;

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
     * @var MeetingModel Meeting for which the error is detected
     */
    protected $meeting;

    /**
     * @return MeetingModel
     */
    public function getMeeting()
    {
        return $this->meeting;
    }

    /**
     * @var SubDecisionModel Decision that caused the error
     * Note that this does not necessarily have to made made during $meeting
     */
    protected $subDecision;

    /**
     * @return SubDecisionModel
     */
    public function getSubDecision()
    {
        return $this->subDecision;
    }

    /**
     * Create a new desciption
     *
     * @param MeetingModel $meeting Meeting for which the error is detected
     * @param SubDecisionModel $subDecision DEcision that caused the error
     */
    public function __construct(
        MeetingModel $meeting,
        SubDecisionModel $subDecision
    ) {
        $this->meeting = $meeting;
        $this->subDecision = $subDecision;
    }


    /**
     * Return a textual representation of the Error
     */
    abstract public function asText();
}
