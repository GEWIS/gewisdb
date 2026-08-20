<?php

declare(strict_types=1);

namespace App\Form\Database\DataMapper;

use App\Entity\Database\Decision;
use App\Entity\Database\Meeting;
use Override;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormInterface;
use Traversable;

use function iterator_to_array;

/**
 * Builds the decision every decision form is about, and hands the sub-decisions to the form that knows them.
 *
 * Sub-decisions are not properties of the decision that property access could reach: a decision form describes an
 * event, and what that event means is a handful of sub-decisions with sequence numbers that only the form can
 * derive. That is what each mapper below adds.
 */
abstract class AbstractDecisionMapper implements DataMapperInterface
{
    /**
     * Decisions are the association's historical record. These forms only ever record a new one, they never read an
     * existing one back into their fields, so there is nothing to map outwards.
     *
     * @param Traversable<mixed, FormInterface> $forms
     */
    #[Override]
    public function mapDataToForms(
        mixed $viewData,
        Traversable $forms,
    ): void {
    }

    /**
     * @param Traversable<mixed, FormInterface> $forms
     */
    #[Override]
    public function mapFormsToData(
        Traversable $forms,
        mixed &$viewData,
    ): void {
        if (!$viewData instanceof Decision) {
            return;
        }

        /** @var array<string, FormInterface> $children */
        $children = iterator_to_array($forms);

        $meeting = $children['meeting']->getData();

        if (!$meeting instanceof Meeting) {
            // Without a meeting there is nothing to hang the decision off; the meeting field reports that itself.
            return;
        }

        // Setting the meeting is what gives the decision its sub-decision collection, so it has to happen before any
        // sub-decision attaches itself to the decision.
        $viewData->setMeeting($meeting);
        $viewData->setPoint((int) $children['point']->getData());
        $viewData->setNumber((int) $children['decision']->getData());

        $this->mapSubDecisions(
            $children,
            $viewData,
        );
    }

    /**
     * Create the sub-decisions this decision consists of and attach them to it.
     *
     * This runs before validation, so anything still missing has to be left alone rather than forced into a setter
     * that does not take null.
     *
     * @param array<string, FormInterface> $forms
     * @param array<string, FormInterface> $forms
     */
    abstract protected function mapSubDecisions(
        array $forms,
        Decision $decision,
    ): void;
}
