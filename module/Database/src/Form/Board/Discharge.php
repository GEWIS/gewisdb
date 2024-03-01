<?php

declare(strict_types=1);

namespace Database\Form\Board;

use Database\Form\AbstractDecision;
use Database\Form\Fieldset\Meeting as MeetingFieldset;
use Database\Form\Fieldset\SubDecision as SubDecisionFieldset;
use Laminas\Form\Element\Submit;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;

/**
 * @psalm-import-type DischargeDecisionFormType from AbstractDecision
 * @extends AbstractDecision<DischargeDecisionFormType>
 */
class Discharge extends AbstractDecision implements InputFilterProviderInterface
{
    public function __construct(
        private readonly Translator $translator,
        MeetingFieldset $meeting,
        SubDecisionFieldset $installation,
    ) {
        parent::__construct($meeting);

        $this->add(clone $installation);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => $this->translator->translate('Discharge Board Member'),
            ],
        ]);
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification(): array
    {
        return [];
    }
}
