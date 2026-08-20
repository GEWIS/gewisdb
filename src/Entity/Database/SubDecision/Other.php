<?php

declare(strict_types=1);

namespace App\Entity\Database\SubDecision;

use App\Entity\Application\Enums\AppLanguages;
use App\Entity\Database\SubDecision;
use App\Repository\Database\SubDecision\OtherRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Override;
use RuntimeException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Entity for undefined decisions.
 */
#[Entity(repositoryClass: OtherRepository::class)]
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

    #[Override]
    protected function getTranslatedTemplate(
        TranslatorInterface $translator,
        AppLanguages $language,
    ): string {
        throw new RuntimeException('Not implemented');
    }

    #[Override]
    public function getTranslatedContent(
        TranslatorInterface $translator,
        AppLanguages $language,
    ): string {
        // The stored content is the (statutory) Dutch text, there is nothing to translate.
        if (AppLanguages::Dutch === $language) {
            return $this->content;
        }

        // No alternative content exists for a custom decision.
        return $translator->trans(
            'If you are reading this, the secretary has not done their job.',
            locale: $language->getLangParam(),
        );
    }
}
