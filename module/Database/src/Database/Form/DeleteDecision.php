<?php

namespace Database\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;

class DeleteDecision extends Form
{
    public function __construct()
    {
        parent::__construct();

        $this->add([
            'name' => 'submit_yes',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Ja'
            ]
        ]);

        $this->add([
            'name' => 'submit_no',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Nee'
            ]
        ]);

        $this->initFilters();
    }

    protected function initFilters()
    {
        $filter = new InputFilter();

        // this filter makes sure that the form is only valid when the user
        // has clicked yes
        $filter->add([
            'name' => 'submit_yes',
            'required' => true
        ]);

        $this->setInputFilter($filter);
    }
}
