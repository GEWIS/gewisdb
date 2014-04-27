<?php

namespace Database\Form;

use Zend\InputFilter\InputFilter;

class Install extends AbstractDecision
{

    public function __construct(Fieldset\Meeting $meeting)
    {
        parent::__construct($meeting);

        $this->add(array(
            'name' => 'name',
            'type' => 'text',
            'options' => array(
                'label' => 'Orgaan',
            )
        ));

        //$this->add(array(
            //'name' => 'members',
            //'type' => 'collection',
            //'options' => array(
                //'label' => 'Members',
                //'count' => 1,
                //'should_create_template' => true,
                //'target_element' => array(
                    //'type' => 'Database\Form\Fieldset\MemberInstall'
                //)
            //)
        //));

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => 'Maak wijzigingen'
            )
        ));
    }
}
