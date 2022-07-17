<?php

namespace Database\Form;

use Database\Form\Fieldset\{
    Meeting as MeetingFieldset,
    SubDecision as SubDecisionFieldset,
};
use Laminas\Form\Element\{
    Submit,
    Text,
};
use Laminas\InputFilter\InputFilterProviderInterface;

class Abolish extends AbstractDecision implements InputFilterProviderInterface
{
    public function __construct(
        MeetingFieldset $meeting,
        SubDecisionFieldset $subdecision,
    ) {
        parent::__construct($meeting);

        $this->add([
            'name' => 'name',
            'type' => Text::class,
            'options' => [
                'label' => 'Orgaan',
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => 'Hef orgaan op',
            ],
        ]);

        $this->add($subdecision);
    }

    public function getInputFilterSpecification(): array
    {
        return [];
    }
}
