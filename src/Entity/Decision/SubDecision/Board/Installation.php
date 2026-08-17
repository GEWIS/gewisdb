<?php

declare(strict_types=1);

namespace App\Entity\Decision\SubDecision\Board;

use App\Entity\Application\Enums\AppLanguages;
use App\Entity\Application\Traits\FormattableDateTrait;
use App\Entity\Decision\Enums\BoardFunctions;
use App\Entity\Decision\SubDecision;
use App\Entity\Decision\Traits\MemberAwareTrait;
use App\Entity\Member\Member;
use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\OneToOne;
use Override;
use Symfony\Contracts\Translation\TranslatorInterface;

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

    #[Override]
    protected function getTranslatedTemplate(
        TranslatorInterface $translator,
        AppLanguages $language,
    ): string {
        return $translator->trans(
            '%MEMBER% wordt per %DATE% geïnstalleerd als %FUNCTION% der s.v. GEWIS.',
            locale: $language->getLangParam(),
        );
    }

    #[Override]
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
