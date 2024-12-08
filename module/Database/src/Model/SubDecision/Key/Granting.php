<?php

declare(strict_types=1);

namespace Database\Model\SubDecision\Key;

use Application\Model\Enums\AppLanguages;
use Database\Model\SubDecision;
use Database\Model\Trait\FormattableDateTrait;
use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\OneToOne;
use Laminas\I18n\Translator\TranslatorInterface;

#[Entity]
class Granting extends SubDecision
{
    use FormattableDateTrait;

    /**
     * Till when the keycode is granted.
     */
    #[Column(type: 'date')]
    protected DateTime $until;

    /**
     * Discharges.
     */
    #[OneToOne(
        targetEntity: Withdrawal::class,
        mappedBy: 'granting',
    )]
    protected ?Withdrawal $withdrawal = null;

    /**
     * Get the date.
     */
    public function getUntil(): DateTime
    {
        return $this->until;
    }

    /**
     * Set the date.
     */
    public function setUntil(DateTime $until): void
    {
        $this->until = $until;
    }

    protected function getTranslatedTemplate(
        TranslatorInterface $translator,
        AppLanguages $language,
    ): string {
        return $translator->translate(
            '%GRANTEE% krijgt een sleutelcode van GEWIS tot en met %UNTIL%.',
            locale: $language->getLangParam(),
        );
    }

    public function getTranslatedContent(
        TranslatorInterface $translator,
        AppLanguages $language,
    ): string {
        $replacements = [
            '%GRANTEE%' => $this->getMember()->getFullName(),
            '%UNTIL%' => $this->formatDate($this->getUntil(), $language),
        ];

        return $this->replaceContentPlaceholders($this->getTranslatedTemplate($translator, $language), $replacements);
    }
}
