<?php

namespace Database\Model\SubDecision;

use Doctrine\ORM\Mapping as ORM;

use Database\Model\SubDecision;
use Database\Model\Member;

/**
 *
 * @ORM\Entity
 */
class Budget extends SubDecision
{

    /**
     * Budget author.
     *
     * @ORM\ManyToOne(targetEntity="Database\Model\Member")
     * @ORM\JoinColumn(name="lidnr", referencedColumnName="lidnr")
     */
    protected $author;

    /**
     * Name of the budget.
     *
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * Version of the budget.
     *
     * @ORM\Column(type="string",length=32)
     */
    protected $version;

    /**
     * Date of the budget.
     *
     * @ORM\Column(type="date")
     */
    protected $date;

    /**
     * If the budget was approved.
     *
     * @ORM\Column(type="boolean")
     */
    protected $approval;

    /**
     * If there were changes made.
     *
     * @ORM\Column(type="boolean")
     */
    protected $changes;

    /**
     * Reference to foundation of organ.
     *
     * This corresponds to the organ of which this budget / reckoning is. This
     * field can be empty, because it might occur that members hand in budgets
     * and/or reckonings that are not tied to organs.
     *
     * @ORM\ManyToOne(targetEntity="Foundation")
     * @ORM\JoinColumns({
     *  @ORM\JoinColumn(name="r_meeting_type", referencedColumnName="meeting_type"),
     *  @ORM\JoinColumn(name="r_meeting_number", referencedColumnName="meeting_number"),
     *  @ORM\JoinColumn(name="r_decision_point", referencedColumnName="decision_point"),
     *  @ORM\JoinColumn(name="r_decision_number", referencedColumnName="decision_number"),
     *  @ORM\JoinColumn(name="r_number", referencedColumnName="number")
     * })
     */
    protected $foundation;

    /**
     * Get the author.
     *
     * @return Member
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set the author.
     *
     * @param Member $author
     */
    public function setAuthor(Member $author)
    {
        $this->author = $author;
    }

    /**
     * Get the name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the name.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get the version.
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set the version.
     *
     * @param string $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * Get the date.
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set the date.
     *
     * @param \DateTime $date
     */
    public function setDate(\DateTime $date)
    {
        $this->date = $date;
    }

    /**
     * Get approval status.
     *
     * @return bool
     */
    public function getApproval()
    {
        return $this->approval;
    }

    /**
     * Set approval status.
     *
     * @param bool $approval
     */
    public function setApproval($approval)
    {
        $this->approval = $approval;
    }

    /**
     * Get if changes were made.
     *
     * @return bool
     */
    public function getChanges()
    {
        return $this->changes;
    }

    /**
     * Set if changes were made.
     *
     * @param bool $changes
     */
    public function setChanges($changes)
    {
        $this->changes = $changes;
    }

    /**
     * Get organ foundation.
     *
     * @return Foundation
     */
    public function getFoundation()
    {
        return $this->foundation;
    }

    /**
     * Set organ foundation.
     *
     * @param Foundation $foundation
     */
    public function setFoundation($foundation)
    {
        $this->foundation = $foundation;
    }

    /**
     * Get the content.
     *
     * @todo implement this
     *
     * @return string
     */
    public function getContent()
    {
        return 'TODO';
    }
}
