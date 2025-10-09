<?php

declare(strict_types=1);

namespace Database\Model\SubDecision;

use Application\Model\Enums\AppLanguages;
use Database\Model\SubDecision;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Laminas\Mvc\I18n\DummyTranslator;
use Laminas\Translator\TranslatorInterface;
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
    private string $content;

    /**
     * Set the content.
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    protected function getTranslatedTemplate(
        TranslatorInterface $translator,
        AppLanguages $language,
    ): string {
        throw new RuntimeException('Not implemented');
    }

    public function getTranslatedContent(
        TranslatorInterface $translator,
        AppLanguages $language,
    ): string {
        if ($translator instanceof DummyTranslator || AppLanguages::Dutch === $language) {
            return $this->content;
        }

        // No alternative content exists for a custom decision.
        return $translator->translate(
            'If you are reading this, the secretary has not done their job.',
            locale: $language->getLangParam(),
        );
    }
}
