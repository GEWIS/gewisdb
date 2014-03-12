<?php

namespace Database\Form\Fieldset;

use Zend\Form\Fieldset;

class Member extends Fieldset
{

    public function __construct()
    {
        parent::__construct('member');

        // is only there for fun, will only be used as source for 'lidnr' autocomplete
        // which uses AJAX to find members
        $this->add(array(
            'name' => 'name',
            'type' => 'text',
            'options' => array(
                'label' => 'Lid'
            )
        ));

        // actual way to find the member
        $this->add(array(
            'name' => 'lidnr',
            'type' => 'hidden',
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
