<?php

namespace Database\Form\Fieldset;

use Zend\Form\Fieldset;

class Member extends Fieldset
{

    public function __construct()
    {
        parent::__construct('member');

        $this->add(array(
            'name' => 'name',
            'type' => 'text',
            'options' => array(
                'label' => 'Lid'
            )
        ));

        $this->add(array(
            'name' => 'function',
            'type' => 'text',
            'options' => array(
                'label' => 'Functie'
            )
        ));
    }
}
