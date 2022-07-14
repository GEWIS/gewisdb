<?php

namespace Database\Form;

use Laminas\InputFilter\InputFilterProviderInterface;

class Destroy extends AbstractDecision implements InputFilterProviderInterface
{
    public function __construct(Fieldset\Meeting $meeting, Fieldset\Decision $decision)
    {
        parent::__construct($meeting);

        $this->add(array(
            'name' => 'name',
            'type' => 'text',
            'options' => array(
                'label' => 'Besluit',
            )
        ));

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => 'Vernietig besluit'
            )
        ));

        $this->add($decision);
    }

    public function getInputFilterSpecification(): array
    {
        return [];
    }
}
