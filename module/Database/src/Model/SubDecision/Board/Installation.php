<?php

declare(strict_types=1);

namespace Database\Model\SubDecision\Board;

use Application\Model\Enums\AppLanguages;
use Database\Model\Enums\BoardFunctions;
use Database\Model\Member;
use Database\Model\SubDecision;
use Database\Model\Trait\FormattableDateTrait;
use DateTime;
use Doctrine\ORM\Mapping\AssociationOverride;
use Doctrine\ORM\Mapping\AssociationOverrides;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use Laminas\Translator\TranslatorInterface;
use Override;

/**
 * Installation as board member.
 */
#[Entity]
#[AssociationOverrides([
    new AssociationOverride(
        name: 'member',
        joinColumns: new JoinColumn(
            name: 'lidnr',
            referencedColumnName: 'lidnr',
            nullable: false,
        ),
    ),
])]
class Installation extends SubDecision
{
    use FormattableDateTrait;

    /**
     * Function given.
     */
    #[Column(
        type: 'string',
        enumType: BoardFunctions::class,
    )]
    protected BoardFunctions $function;

    /**
     * The date at which the installation is in effect.
     */
    #[Column(type: 'date')]
    protected DateTime $date;

    /**
     * Discharge.
     */
    #[OneToOne(
        targetEntity: Discharge::class,
        mappedBy: 'installation',
    )]
    protected ?Discharge $discharge = null;

    /**
     * Release.
     */
    #[OneToOne(
        targetEntity: Release::class,
        mappedBy: 'installation',
    )]
    protected ?Release $release = null;

    /**
     * Get the function.
     */
    public function getFunction(): BoardFunctions
    {
        return $this->function;
    }

    /**
     * Set the function.
     */
    public function setFunction(BoardFunctions $function): void
    {
        $this->function = $function;
    }

    /**
     * Get the member.
     *
     * @psalm-suppress InvalidNullableReturnType
     */
    #[Override]
    public function getMember(): Member
    {
        return $this->member;
    }

    /**
     * Get the date.
     */
    public function getDate(): DateTime
    {
        return $this->date;
    }

    /**
     * Set the date.
     */
    public function setDate(DateTime $date): void
    {
        $this->date = $date;
    }

    protected function getTranslatedTemplate(
        TranslatorInterface $translator,
        AppLanguages $language,
    ): string {
        return $translator->translate(
            '%MEMBER% wordt per %DATE% geïnstalleerd als %FUNCTION% der s.v. GEWIS.',
            locale: $language->getLangParam(),
        );
    }

    public function getTranslatedContent(
        TranslatorInterface $translator,
        AppLanguages $language,
    ): string {
        $replacements = [
            '%MEMBER%' => $this->getMember()->getFullName(),
            '%DATE%' => $this->formatDate($this->getDate(), $language),
            '%FUNCTION%' => $this->getFunction()->getName($translator, $language),
        ];

        return $this->replaceContentPlaceholders($this->getTranslatedTemplate($translator, $language), $replacements);
    }
}
