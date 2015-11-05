<?php

namespace Database\Form;

use Zend\InputFilter\InputFilter;
use Database\Model\SubDecision;

class Other extends AbstractDecision
{

    public function __construct(Fieldset\Meeting $meeting)
    {
        parent::__construct($meeting);

        $this->add(array(
            'name' => 'content',
            'type' => 'text',
            'options' => array(
                'label' => 'Besluit',
            )
        ));

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => 'Verzend'
            )
        ));

        $this->initFilters();
    }

    protected function initFilters()
    {
        $filter = new InputFilter();

        $filter->add(array(
            'name' => 'content',
            'required' => true,
            'validators' => array(
                array(
                    'name' => 'string_length',
                    'options' => array(
                        'min' => 3
                    )
                )
            )
        ));

        $this->setInputFilter($filter);
    }
}
