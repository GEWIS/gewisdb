<?php

declare(strict_types=1);

namespace Database\Model\SubDecision;

use Application\Model\Enums\AppLanguages;
use Application\Model\Enums\MeetingTypes;
use Application\Model\Enums\OrganTypes;
use Database\Model\SubDecision;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\OneToMany;
use Laminas\Translator\TranslatorInterface;

/**
 * Foundation of an organ.
 */
#[Entity]
class Foundation extends SubDecision
{
    /**
     * Abbreviation (only for when organs are created)
     */
    #[Column(type: 'string')]
    private string $abbr;

    /**
     * Name (only for when organs are created)
     */
    #[Column(type: 'string')]
    private string $name;

    /**
     * Type of the organ.
     */
    #[Column(
        type: 'string',
        enumType: OrganTypes::class,
    )]
    private OrganTypes $organType;

    /**
     * References from other subdecisions to this organ.
     *
     * @var Collection<array-key, FoundationReference>
     */
    #[OneToMany(
        targetEntity: FoundationReference::class,
        mappedBy: 'foundation',
    )]
    private Collection $references;

    public function __construct()
    {
        $this->references = new ArrayCollection();
    }

    /**
     * Get the abbreviation.
     */
    public function getAbbr(): string
    {
        return $this->abbr;
    }

    /**
     * Set the abbreviation.
     */
    public function setAbbr(string $abbr): void
    {
        $this->abbr = $abbr;
    }

    /**
     * Get the name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the name.
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get the type.
     */
    public function getOrganType(): OrganTypes
    {
        return $this->organType;
    }

    /**
     * Set the type.
     */
    public function setOrganType(OrganTypes $organType): void
    {
        $this->organType = $organType;
    }

    /**
     * Get the references.
     *
     * @return Collection<array-key, FoundationReference>
     */
    public function getReferences(): Collection
    {
        return $this->references;
    }

    protected function getTranslatedTemplate(
        TranslatorInterface $translator,
        AppLanguages $language,
    ): string {
        if (OrganTypes::SC !== $this->getOrganType()) {
            return $translator->translate(
                '%ORGAN_TYPE% %ORGAN_NAME% met afkorting %ORGAN_ABBR% wordt opgericht.',
                locale: $language->getLangParam(),
            );
        }

        return $translator->translate(
            'De stemcommissie van de %MEETING_NUMBER%e ALV met afkorting %ORGAN_ABBR% wordt opgericht.',
            locale: $language->getLangParam(),
        );
    }

    public function getTranslatedContent(
        TranslatorInterface $translator,
        AppLanguages $language,
    ): string {
        $replacements = [
            '%ORGAN_ABBR%' => $this->getAbbr(),
        ];

        if (OrganTypes::SC !== $this->getOrganType()) {
            $replacements += [
                '%ORGAN_TYPE%' => $this->getOrganType()->getName($translator, $language),
                '%ORGAN_NAME%' => $this->getName(),
            ];
        } else {
            $replacements['%MEETING_NUMBER%'] = $this->getMeetingNumber();
        }

        /** @psalm-suppress InvalidArgument */
        return $this->replaceContentPlaceholders($this->getTranslatedTemplate($translator, $language), $replacements);
    }

    /**
     * Get an array with all information.
     *
     * Mostly useful for usage with JSON.
     *
     * @return array{
     *     meeting_type: MeetingTypes,
     *     meeting_number: int,
     *     decision_point: int,
     *     decision_number: int,
     *     subdecision_sequence: int,
     *     abbr: string,
     *     name: string,
     *     organtype: OrganTypes,
     * }
     */
    public function toArray(): array
    {
        $decision = $this->getDecision();

        return [
            'meeting_type' => $decision->getMeeting()->getType(),
            'meeting_number' => $decision->getMeeting()->getNumber(),
            'decision_point' => $decision->getPoint(),
            'decision_number' => $decision->getNumber(),
            'subdecision_sequence' => $this->getSequence(),
            'abbr' => $this->getAbbr(),
            'name' => $this->getName(),
            'organtype' => $this->getOrganType(),
        ];
    }
}
