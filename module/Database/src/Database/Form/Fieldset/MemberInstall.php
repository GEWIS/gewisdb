<?php

namespace Database\Form\Fieldset;

class MemberInstall extends Member
{

    public function __construct()
    {
        parent::__construct();

        $this->add(array(
            'name' => 'type',
            'type' => 'select',
            'options' => array(
                'label' => 'Installatie / Decharge',
                'value_options' => array(
                    'install' => 'Installeer',
                    'discharge' => 'Dechargeer',
                )
            )
        ));

        $this->remove('function');
    }
}
