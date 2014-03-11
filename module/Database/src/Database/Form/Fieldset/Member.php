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
            'type' => 'select',
            'options' => array(
                'label' => 'Functie',
                'value_options' => array(
                    'lid' => 'Lid',
                    'vz' => 'Voorzitter',
                    'secr' => 'Secretaris'
                )
            )
        ));
    }
}
