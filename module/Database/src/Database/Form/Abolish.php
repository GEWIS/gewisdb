<?php

namespace Database\Form;

use Zend\InputFilter\InputFilter;

class Abolish extends AbstractDecision
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

        $this->initFilters();
    }

    protected function initFilters()
    {
        $filter = new InputFilter();

        $this->setInputFilter($filter);
    }
}
