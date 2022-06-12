<?php

namespace Database\Model\SubDecision;

use Doctrine\ORM\Mapping as ORM;
use Database\Model\SubDecision;

/**
 * Entity for undefined decisions.
 *
 * @ORM\Entity
 */
class Other extends SubDecision
{
    /**
     * Textual content for the decision.
     *
     * @ORM\Column(type="text")
     */
    protected $content;


    /**
     * Get the content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set the content.
     *
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }
}
