<?php

namespace Database\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;

class Install extends Form
{

    public function __construct()
    {
        parent::__construct();

        $this->add(array(
            'name' => 'name',
            'type' => 'text',
            'options' => array(
                'label' => 'Orgaan',
            )
        ));

        $this->add(array(
            'name' => 'members',
            'type' => 'collection',
            'options' => array(
                'label' => 'Members',
                'count' => 1,
                'should_create_template' => true,
                'target_element' => array(
                    'type' => 'Database\Form\Fieldset\MemberInstall'
                )
            )
        ));

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => 'Installeer leden'
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
