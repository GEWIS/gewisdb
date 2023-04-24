<?php

declare(strict_types=1);

namespace Database\Model\SubDecision;

use Database\Model\SubDecision;
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
};

/**
 * Entity for undefined decisions.
 */
#[Entity]
class Other extends SubDecision
{
    /**
     * Textual content for the decision.
     */
    #[Column(type: "text")]
    protected string $content;

    /**
     * Get the content.
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Set the content.
     *
     * @param string $content
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
    }
}
