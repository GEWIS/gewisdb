<?php

namespace Database\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;

class DeleteDecision extends Form
{

    public function __construct()
    {
        parent::__construct();

        $this->add(array(
            'name' => 'submit_yes',
            'type' => 'submit',
            'attributes' => array(
                'value' => 'Ja'
            )
        ));

        $this->add(array(
            'name' => 'submit_no',
            'type' => 'submit',
            'attributes' => array(
                'value' => 'Nee'
            )
        ));

        $this->initFilters();
    }

    protected function initFilters()
    {
        $filter = new InputFilter();

        // this filter makes sure that the form is only valid when the user
        // has clicked yes
        $filter->add(array(
            'name' => 'submit_yes',
            'required' => true
        ));

        $this->setInputFilter($filter);
    }
}
