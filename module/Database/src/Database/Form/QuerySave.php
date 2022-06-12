<?php

namespace Database\Form;

class QuerySave extends Query
{
    public function __construct()
    {
        parent::__construct();

        $this->add([
            'name' => 'name',
            'type' => 'text',
            'options' => [
                'label' => 'Naam'
            ]
        ]);

        $this->add([
            'name' => 'submit_save',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Opslaan'
            ]
        ]);

        $this->get('submit_save')->setLabel('Opslaan');
    }
}
