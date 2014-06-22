<?php

namespace Database\Model\SubDecision;

use Doctrine\ORM\Mapping as ORM;

use Database\Model\SubDecision;
use Database\Model\Member;

/**
 *
 * @ORM\Entity
 */
class Budget extends FoundationReference
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
     * Get the content.
     *
     * @return string
     */
    public function getContent()
    {
        $template = $this->getTemplate();
        $template = str_replace('%NAME%', $this->getName(), $template);
        $template = str_replace('%AUTHOR%', $this->getAuthor()->getFullName(), $template);
        $template = str_replace('%VERSION%', $this->getVersion(), $template);
        $template = str_replace('%DATE%', $this->formatDate($this->getDate()), $template);
        if ($this->getApproval()) {
            $template = str_replace('%APPROVAL%', 'goedgekeurd', $template);
            if ($this->getChanges()) {
                $template = str_replace('%CHANGES%', ' met genoemde wijzigingen', $template);
            } else {
                $template = str_replace('%CHANGES%', '', $template);
            }
        } else {
            $template = str_replace('%APPROVAL%', 'afgekeurd', $template);
            $template = str_replace('%CHANGES%', '', $template);
        }
        return $template;
    }

    /**
     * Format the date.
     *
     * returns the localized version of $date->format('d F Y')
     *
     * @param DateTime $date
     *
     * @return string Formatted date
     */
    protected function formatDate(\DateTime $date)
    {
        $formatter = new \IntlDateFormatter(
            'nl_NL', // yes, hardcoded :D
            \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE,
            \date_default_timezone_get(),
            null,
            'd MMMM Y'
        );
        return $formatter->format($date);
    }

    /**
     * Decision template
     *
     * @return string
     */
    protected function getTemplate()
    {
        return 'De begroting %NAME% van %AUTHOR%, versie %VERSION% van %DATE% wordt %APPROVAL%%CHANGES%.';
    }
}
