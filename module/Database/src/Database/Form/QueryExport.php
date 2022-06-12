<?php

namespace Database\Form;

class QueryExport extends Query
{
    public function __construct()
    {
        parent::__construct();

        $this->add([
            'name' => 'type',
            'type' => 'select',
            'options' => [
                'value_options' => [
                    'csv' => 'CSV'
                ]
            ]
        ]);

        $this->get('submit')->setAttribute('value', 'export');
        $this->get('submit')->setLabel('Exporteer');
    }
}
