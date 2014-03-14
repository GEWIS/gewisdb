<?php

namespace Database\Form\Fieldset;

class MemberFunction extends Member
{

    public function __construct()
    {
        parent::__construct();

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
