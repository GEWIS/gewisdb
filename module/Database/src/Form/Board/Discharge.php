<?php

namespace Database\Form\Board;

use Database\Form\AbstractDecision;
use Database\Form\Fieldset\{
    Meeting as MeetingFieldset,
    SubDecision as SubDecisionFieldset,
};
use Laminas\Form\Element\Submit;
use Laminas\InputFilter\InputFilterProviderInterface;

class Discharge extends AbstractDecision implements InputFilterProviderInterface
{
    public function __construct(
        MeetingFieldset $meeting,
        SubDecisionFieldset $installation,
    ) {
        parent::__construct($meeting);

        $this->add(clone $installation);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => 'Dechargeer bestuurder',
            ],
        ]);
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification(): array
    {
        return [
        ];
    }
}
