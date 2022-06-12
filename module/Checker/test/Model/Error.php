<?php

/**
 * Created by PhpStorm.
 * User: stefan
 * Date: 8-2-15
 * Time: 12:43
 */

namespace CheckerTest\Model;

use Database\Model\Decision;
use Database\Model\Meeting;
use Database\Model\Member;
use Database\Model\SubDecision\Foundation;

abstract class Error extends \PHPUnit_Framework_TestCase
{
    // Create a new error
    abstract protected function create();

    public function getMeeting()
    {
        return new Meeting();
    }

    protected function getDecision()
    {
        $decision = new Decision();
        $decision->setMeeting($this->getMeeting());
        return $decision;
    }

    protected function getFoundation()
    {
        $foundation = new Foundation();
        $foundation->setDecision($this->getDecision());
        return $foundation;
    }

    protected function getMember()
    {
        $member = new Member();
        return $member;
    }

    public function testGetSubDecision()
    {
        $error = $this->create();
        $this->assertInstanceOf('Database\Model\SubDecision', $error->getSubDecision());
    }

    public function testGetMeeting()
    {
        $error = $this->create();
        $this->assertInstanceOf('Database\Model\Meeting', $error->getMeeting());
    }

    public function testAsText()
    {
        $error = $this->create();
        $this->assertTrue(is_string($error->asText()));
    }
}
