<?php

declare(strict_types=1);

namespace Database\Model;

use Application\Model\Enums\AppLanguages;
use Application\Model\Enums\MeetingTypes;
use Database\Model\SubDecision\Abrogation;
use Database\Model\SubDecision\Annulment;
use Database\Model\SubDecision\Board\Discharge as BoardDischarge;
use Database\Model\SubDecision\Board\Installation as BoardInstallation;
use Database\Model\SubDecision\Board\Release as BoardRelease;
use Database\Model\SubDecision\Discharge;
use Database\Model\SubDecision\Financial\Budget;
use Database\Model\SubDecision\Financial\Statement;
use Database\Model\SubDecision\Foundation;
use Database\Model\SubDecision\FoundationReference;
use Database\Model\SubDecision\Installation;
use Database\Model\SubDecision\Key\Granting as KeyGranting;
use Database\Model\SubDecision\Key\Withdrawal as KeyWithdrawal;
use Database\Model\SubDecision\Minutes;
use Database\Model\SubDecision\OrganRegulation;
use Database\Model\SubDecision\Other;
use Database\Model\SubDecision\Reappointment;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Laminas\I18n\Translator\TranslatorInterface;
use Laminas\Mvc\I18n\Translator;

use function array_keys;
use function str_replace;

/**
 * SubDecision model.
 */
#[Entity]
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
        type: 'string',
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
     * The member involved in this sub-decision.
     *
     * Not all sub-decisions require this, as such it is nullable. However, sub-decisions that need the guarantee that
     * this is not null or need to specify an inverse side can do so using an association override.
     */
    #[ManyToOne(targetEntity: Member::class)]
    #[JoinColumn(
        name: 'lidnr',
        referencedColumnName: 'lidnr',
        nullable: true,
    )]
    protected ?Member $member = null;

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
     * Get the member.
     */
    public function getMember(): ?Member
    {
        return $this->member;
    }

    /**
     * Set the sequence number.
     */
    public function setSequence(int $sequence): void
    {
        $this->sequence = $sequence;
    }

    /**
     * Set the member.
     */
    public function setMember(Member $member): void
    {
        $this->member = $member;
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
     * Get the statutory content of the subdecision
     */
    // final public function getContent(): string
    // {
    //     $translator = new DummyTranslator();

    //     return $this->getTranslatedContent($translator, AppLanguages::Dutch);
    // }

    /**
     * Get the content in the current language (requires real translator)
     */
    final public function getContent(Translator $translator): string
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
