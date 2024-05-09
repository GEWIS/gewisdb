<?php

declare(strict_types=1);

namespace Database\Model\SubDecision;

use Database\Model\SubDecision;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use RuntimeException;

/**
 * Entity for undefined decisions.
 */
#[Entity]
class Other extends SubDecision
{
    /**
     * Textual content for the decision.
     */
    #[Column(type: 'text')]
    protected string $content;

    /**
     * Get the content.
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Set the content.
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    protected function getTemplate(): string
    {
        throw new RuntimeException('Not implemented');
    }

    protected function getAlternativeTemplate(): string
    {
        throw new RuntimeException('Not implemented');
    }

    public function getAlternativeContent(): string
    {
        return $this->getContent(); // No alternative content exists for a custom decision.
    }
}
