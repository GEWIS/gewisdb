<?php

declare(strict_types=1);

namespace App\Entity\Decision\SubDecision\Key;

use App\Entity\Application\Enums\AppLanguages;
use App\Entity\Application\Traits\FormattableDateTrait;
use App\Entity\Decision\SubDecision;
use App\Entity\Decision\Traits\MemberAwareTrait;
use App\Entity\Member\Member;
use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\OneToOne;
use Override;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Entity]
class Granting extends SubDecision
{
    use FormattableDateTrait;
    use MemberAwareTrait;

    /**
     * Till when the keycode is granted.
     */
    #[Column(type: 'date')]
    private DateTime $until;

    /**
     * Discharges.
     */
    #[OneToOne(
        targetEntity: Withdrawal::class,
        mappedBy: 'granting',
    )]
    private ?Withdrawal $withdrawal = null;

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

    #[Override]
    protected function getTranslatedTemplate(
        TranslatorInterface $translator,
        AppLanguages $language,
    ): string {
        return $translator->trans(
            '%GRANTEE% krijgt een sleutelcode van GEWIS tot en met %UNTIL%.',
            locale: $language->getLangParam(),
        );
    }

    #[Override]
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
