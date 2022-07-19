<?php

namespace Database\Form\Board;

use Database\Form\AbstractDecision;
use Database\Form\Fieldset\{
    Meeting as MeetingFieldset,
    SubDecision as SubDecisionFieldset,
};
use Laminas\Form\Element\{
    Date,
    Submit,
};
use Laminas\InputFilter\InputFilterProviderInterface;

class Release extends AbstractDecision implements InputFilterProviderInterface
{
    public function __construct(
        MeetingFieldset $meeting,
        SubDecisionFieldset $installation,
    ) {
        parent::__construct($meeting);

        $this->add(clone $installation);

        $this->add([
            'name' => 'date',
            'type' => Date::class,
            'options' => [
                'label' => 'Van kracht per',
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => 'Onthef bestuurder',
            ],
        ]);
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'date' => [
                'required' => true,
            ],
        ];
    }
}
