<?php

namespace Database\Form;

use Laminas\InputFilter\InputFilterProviderInterface;

class Destroy extends AbstractDecision implements InputFilterProviderInterface
{
    public function __construct(Fieldset\Meeting $meeting, Fieldset\Decision $decision)
    {
        parent::__construct($meeting);

        $this->add([
            'name' => 'name',
            'type' => 'text',
            'options' => [
                'label' => 'Besluit',
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
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
