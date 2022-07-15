<?php

namespace Database\Form;

use Laminas\InputFilter\InputFilterProviderInterface;

class Abolish extends AbstractDecision implements InputFilterProviderInterface
{
    public function __construct(
        Fieldset\Meeting $meeting,
        Fieldset\SubDecision $subdecision,
    ) {
        parent::__construct($meeting);

        $this->add([
            'name' => 'name',
            'type' => 'text',
            'options' => [
                'label' => 'Orgaan',
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
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
