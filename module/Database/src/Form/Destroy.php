<?php

namespace Database\Form;

use Database\Form\Fieldset\{
    Meeting as MeetingFieldset,
    Decision as DecisionFieldset,
};
use Laminas\Form\Element\{
    Submit,
    Text,
};
use Laminas\InputFilter\InputFilterProviderInterface;

class Destroy extends AbstractDecision implements InputFilterProviderInterface
{
    public function __construct(
        MeetingFieldset $meeting,
        DecisionFieldset $decision,
    ) {
        parent::__construct($meeting);

        $this->add([
            'name' => 'name',
            'type' => Text::class,
            'options' => [
                'label' => 'Besluit',
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => 'Vernietig besluit',
            ],
        ]);

        $this->add($decision);
    }

    public function getInputFilterSpecification(): array
    {
        return [];
    }
}
