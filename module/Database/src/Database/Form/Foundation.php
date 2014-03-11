<?php

namespace Database\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;

class Foundation extends Form
{

    public function __construct()
    {
        parent::__construct();

        $this->add(array(
            'name' => 'type',
            'type' => 'radio',
            'options' => array(
                'label' => 'Type',
                'value_options' => array(
                    'commissie' => 'Commissie',
                    'dispuut' => 'Dispuut'
                )
            )
        ));

        $this->add(array(
            'name' => 'name',
            'type' => 'text',
            'options' => array(
                'label' => 'Naam',
            )
        ));

        $this->add(array(
            'name' => 'abbr',
            'type' => 'text',
            'options' => array(
                'label' => 'Afkorting'
            )
        ));

        $this->add(array(
            'name' => 'members',
            'type' => 'collection',
            'options' => array(
                'label' => 'Members',
                'count' => 2,
                'should_create_template' => true,
                'target_element' => array(
                    'type' => 'Database\Form\Fieldset\Member'
                )
            )
        ));

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => 'Richt op'
            )
        ));

        $this->initFilters();

    }

    protected function initFilters()
    {
        $filter = new InputFilter();

        $this->setInputFilter($filter);
    }
}
