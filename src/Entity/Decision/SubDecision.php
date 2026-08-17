<?php

declare(strict_types=1);

namespace App\Entity\Decision;

use App\Entity\Application\Enums\AppLanguages;
use App\Entity\Decision\Enums\MeetingTypes;
use App\Entity\Decision\SubDecision\Abrogation;
use App\Entity\Decision\SubDecision\Annulment;
use App\Entity\Decision\SubDecision\Board\Discharge as BoardDischarge;
use App\Entity\Decision\SubDecision\Board\Installation as BoardInstallation;
use App\Entity\Decision\SubDecision\Board\Release as BoardRelease;
use App\Entity\Decision\SubDecision\Discharge;
use App\Entity\Decision\SubDecision\Financial\Budget;
use App\Entity\Decision\SubDecision\Financial\Statement;
use App\Entity\Decision\SubDecision\Foundation;
use App\Entity\Decision\SubDecision\FoundationReference;
use App\Entity\Decision\SubDecision\Installation;
use App\Entity\Decision\SubDecision\Key\Granting as KeyGranting;
use App\Entity\Decision\SubDecision\Key\Withdrawal as KeyWithdrawal;
use App\Entity\Decision\SubDecision\Minutes;
use App\Entity\Decision\SubDecision\OrganRegulation;
use App\Entity\Decision\SubDecision\Other;
use App\Entity\Decision\SubDecision\Reappointment;
use App\Repository\Decision\SubDecisionRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_keys;
use function str_replace;

/**
 * SubDecision model.
 */
#[Entity(repositoryClass: SubDecisionRepository::class)]
#[InheritanceType(value: 'SINGLE_TABLE')]
#[DiscriminatorColumn(
    name: 'type',
    type: 'string',
)]
#[DiscriminatorMap(
    value: [
        'organ_regulation' => OrganRegulation::class,
        'foundation' => Foundation::class,
        'abrogation' => Abrogation::class,
        'installation' => Installation::class,
        'reappointment' => Reappointment::class,
        'discharge' => Discharge::class,
        'financial_budget' => Budget::class,
        'financial_statement' => Statement::class,
        'other' => Other::class,
        'annulment' => Annulment::class,
        'board_installation' => BoardInstallation::class,
        'board_release' => BoardRelease::class,
        'board_discharge' => BoardDischarge::class,
        'foundationreference' => FoundationReference::class,
        'key_granting' => KeyGranting::class,
        'key_withdraw' => KeyWithdrawal::class,
        'minutes' => Minutes::class,
    ],
)]
abstract class SubDecision
{
    /**
     * Decision.
     */
    #[ManyToOne(
        targetEntity: Decision::class,
        inversedBy: 'subdecisions',
    )]
    #[JoinColumn(
        name: 'meeting_type',
        referencedColumnName: 'meeting_type',
    )]
    #[JoinColumn(
        name: 'meeting_number',
        referencedColumnName: 'meeting_number',
    )]
    #[JoinColumn(
        name: 'decision_point',
        referencedColumnName: 'point',
    )]
    #[JoinColumn(
        name: 'decision_number',
        referencedColumnName: 'number',
    )]
    protected Decision $decision;

    /**
     * Meeting type.
     *
     * NOTE: This is a hack to make the decision a primary key here.
     */
    #[Id]
    #[Column(
        enumType: MeetingTypes::class,
    )]
    protected MeetingTypes $meeting_type;

    /**
     * Meeting number
     *
     * NOTE: This is a hack to make the decision a primary key here.
     */
    #[Id]
    #[Column(type: 'integer')]
    protected int $meeting_number;

    /**
     * Decision point.
     *
     * NOTE: This is a hack to make the decision a primary key here.
     */
    #[Id]
    #[Column(type: 'integer')]
    protected int $decision_point;

    /**
     * Decision number.
     *
     * NOTE: This is a hack to make the decision a primary key here.
     */
    #[Id]
    #[Column(type: 'integer')]
    protected int $decision_number;

    /**
     * Sub decision sequence number.
     */
    #[Id]
    #[Column(type: 'integer')]
    protected int $sequence;

    /**
     * Get the decision.
     */
    public function getDecision(): Decision
    {
        return $this->decision;
    }

    /**
     * Set the decision.
     */
    public function setDecision(Decision $decision): void
    {
        $decision->addSubdecision($this);
        $this->meeting_type = $decision->getMeetingType();
        $this->meeting_number = $decision->getMeetingNumber();
        $this->decision_point = $decision->getPoint();
        $this->decision_number = $decision->getNumber();
        $this->decision = $decision;
    }

    /**
     * Get the meeting type.
     */
    public function getMeetingType(): MeetingTypes
    {
        return $this->meeting_type;
    }

    /**
     * Get the meeting number.
     */
    public function getMeetingNumber(): int
    {
        return $this->meeting_number;
    }

    /**
     * Get the decision point number.
     */
    public function getDecisionPoint(): int
    {
        return $this->decision_point;
    }

    /**
     * Get the decision number.
     */
    public function getDecisionNumber(): int
    {
        return $this->decision_number;
    }

    /**
     * Get the sequence number.
     */
    public function getSequence(): int
    {
        return $this->sequence;
    }

    /**
     * Set the sequence number.
     */
    public function setSequence(int $sequence): void
    {
        $this->sequence = $sequence;
    }

    /**
     * Get the template string for the alternative content of the subdecision in a specified language.
     * A decision was made to let the statutory content be the translation template.
     * Hence, if no translation to English is available, the Dutch text will be shown.
     *
     * Any changes to this method should also be reflected in {@see SubDecision::getTemplate()}.
     */
    abstract protected function getTranslatedTemplate(
        TranslatorInterface $translator,
        AppLanguages $language,
    ): string;

    /**
     * Perform string replacements on a template.
     *
     * Used in the implementations of {@see SubDecision::getContent()} and {@see SubDecision::getAlternativeContent()}.
     *
     * @param array<string, string> $replacements
     */
    protected function replaceContentPlaceholders(
        string $template,
        array $replacements,
    ): string {
        return str_replace(array_keys($replacements), $replacements, $template);
    }

    /**
     * Get the content of the subdecision in the translator's current language.
     */
    final public function getContent(TranslatorInterface $translator): string
    {
        $language = AppLanguages::fromLangParam($translator->getLocale());

        return $this->getTranslatedContent($translator, $language);
    }

    /**
     * Get the content of the subdecision in a specified language.
     */
    abstract public function getTranslatedContent(
        TranslatorInterface $translator,
        AppLanguages $language,
    ): string;
}
