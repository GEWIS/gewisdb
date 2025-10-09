<?php

declare(strict_types=1);

namespace Database\Model\SubDecision\Board;

use Application\Model\Enums\AppLanguages;
use Database\Model\Enums\BoardFunctions;
use Database\Model\Member;
use Database\Model\SubDecision;
use Database\Model\Trait\FormattableDateTrait;
use Database\Model\Trait\MemberAwareTrait;
use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\OneToOne;
use Laminas\Translator\TranslatorInterface;

/**
 * Installation as board member.
 */
#[Entity]
class Installation extends SubDecision
{
    use FormattableDateTrait;
    use MemberAwareTrait;

    /**
     * Function given.
     */
    #[Column(
        type: 'string',
        enumType: BoardFunctions::class,
    )]
    private BoardFunctions $function;

    /**
     * The date at which the installation is in effect.
     */
    #[Column(type: 'date')]
    private DateTime $date;

    /**
     * Discharge.
     */
    #[OneToOne(
        targetEntity: Discharge::class,
        mappedBy: 'installation',
    )]
    private ?Discharge $discharge = null;

    /**
     * Release.
     */
    #[OneToOne(
        targetEntity: Release::class,
        mappedBy: 'installation',
    )]
    private ?Release $release = null;

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
