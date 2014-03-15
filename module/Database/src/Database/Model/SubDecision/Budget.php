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
}
