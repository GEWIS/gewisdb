<?php

namespace Database\Form;

use Zend\InputFilter\InputFilter;
use Database\Model\SubDecision;

class Other extends AbstractDecision
{
    public function __construct(Fieldset\Meeting $meeting)
    {
        parent::__construct($meeting);

        $this->add([
            'name' => 'content',
            'type' => 'text',
            'options' => [
                'label' => 'Besluit',
            ]
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Verzend'
            ]
        ]);

        $this->initFilters();
    }

    protected function initFilters()
    {
        $filter = new InputFilter();

        $filter->add([
            'name' => 'content',
            'required' => true,
            'validators' => [
                [
                    'name' => 'string_length',
                    'options' => [
                        'min' => 3
                    ]
                ]
            ]
        ]);

        $this->setInputFilter($filter);
    }
}
