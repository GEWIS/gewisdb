<?php

namespace Database\Form;

use Laminas\InputFilter\InputFilterProviderInterface;

class Abolish extends AbstractDecision implements InputFilterProviderInterface
{
    public function __construct(
        Fieldset\Meeting $meeting,
        Fieldset\SubDecision $subdecision
    ) {
        parent::__construct($meeting);

        $this->add(array(
            'name' => 'name',
            'type' => 'text',
            'options' => array(
                'label' => 'Orgaan',
            )
        ));

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => 'Hef orgaan op'
            )
        ));

        $this->add($subdecision);
    }

    public function getInputFilterSpecification(): array
    {
        return [];
    }
}
