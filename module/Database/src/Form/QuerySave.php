<?php

namespace Database\Form;

class QuerySave extends Query
{
    public function __construct()
    {
        parent::__construct();

        $this->add(array(
            'name' => 'name',
            'type' => 'text',
            'options' => array(
                'label' => 'Naam'
            )
        ));

        $this->add(array(
            'name' => 'submit_save',
            'type' => 'submit',
            'attributes' => array(
                'value' => 'Opslaan'
            )
        ));

        $this->get('submit_save')->setLabel('Opslaan');
    }
}
