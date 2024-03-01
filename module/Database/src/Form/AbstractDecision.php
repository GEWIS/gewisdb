<?php

declare(strict_types=1);

namespace Database\Form;

use Database\Form\Fieldset\Decision as DecisionFieldset;
use Database\Form\Fieldset\Meeting as MeetingFieldset;
use Database\Form\Fieldset\Member as MemberFieldset;
use Database\Form\Fieldset\SubDecision as SubDecisionFieldset;
use Database\Model\Meeting as MeetingModel;
use Laminas\Form\Element\Hidden;
use Laminas\Form\Form;

// "of AbstractDecisionFormType" should be added but cannot be reliably added
/**
 * @template AbstractDecisionFormTypeTemplate
 *
 * @psalm-import-type MeetingType from MeetingFieldset
 * @psalm-type AbstractDecisionFormTypeExtendable = array{
 *  meeting: MeetingType,
 *  point: int,
 *  decision: int,
 * }
 * @psalm-type AbstractDecisionFormType = array{
 *  ... <array-key, mixed>,
 * } & AbstractDecisionFormTypeExtendable
 *
 * @psalm-type AbolishDecisionFormType = object{
 *  name: string,
 *  subdecision: SubDecisionFieldsetType
 * }
 *
 * @psalm-type BudgetDecisionFormType = array{
 *  type: 'budget'|'reckoning',
 *  name: string,
 *  date: string,
 *  author: MemberFieldsetType,
 *  version: string,
 *  approve: bool,
 *  changes: bool,
 * } & AbstractDecisionFormType
 *
 * @psalm-import-type DecisionFieldsetType from DecisionFieldset
 * @psalm-type DestroyDecisionFormType = array{
 *  name: string,
 *  decision: DecisionFieldsetType,
 * }
 *
 * @psalm-import-type SubDecisionFieldsetType from SubDecisionFieldset
 * @psalm-type DischargeDecisionFormType = array{
 *  installation: SubDecisionFieldsetType,
 * } & AbstractDecisionFormTypeExtendable
 *
 * @psalm-import-type MemberFieldsetType from MemberFieldset
 * @psalm-type InstallDecisionFormType = array{
 *  member: MemberFieldsetType,
 *  function: string,
 *  date: string,
 * } & AbstractDecisionFormTypeExtendable
 *
 * @psalm-type ReleaseDecisionFormType = array{
 *  installation: SubDecisionFieldsetType,
 *  date: string,
 * }
 *
 * @extends Form<AbstractDecisionFormTypeTemplate>
 */
abstract class AbstractDecision extends Form
{
    public function __construct(MeetingFieldset $meeting)
    {
        parent::__construct();

        $meeting->setName('meeting');
        $this->add($meeting);

        $this->add([
            'name' => 'point',
            'type' => Hidden::class,
        ]);

        $this->add([
            'name' => 'decision',
            'type' => Hidden::class,
        ]);

        // TODO: filters
    }

    /**
     * Set data for the decision.
     */
    public function setDecisionData(
        MeetingModel $meeting,
        int $point,
        int $decision,
    ): void {
        $this->get('meeting')->setMeetingData($meeting);
        $this->get('point')->setValue($point);
        $this->get('decision')->setValue($decision);
    }
}
