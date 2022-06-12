<?php

namespace Database\Form;

use Zend\InputFilter\InputFilter;

class Destroy extends AbstractDecision
{
    public function __construct(Fieldset\Meeting $meeting, Fieldset\Decision $decision)
    {
        parent::__construct($meeting);

        $this->add([
            'name' => 'name',
            'type' => 'text',
            'options' => [
                'label' => 'Besluit',
            ]
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Vernietig besluit'
            ]
        ]);

        $this->add($decision);

        $this->initFilters();
    }

    protected function initFilters()
    {
        $filter = new InputFilter();

        $this->setInputFilter($filter);
    }
}
