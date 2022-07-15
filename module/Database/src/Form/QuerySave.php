<?php

namespace Database\Form;

use Laminas\InputFilter\InputFilterProviderInterface;

class QuerySave extends Query implements InputFilterProviderInterface
{
    public function __construct()
    {
        parent::__construct();

        $this->add([
            'name' => 'name',
            'type' => 'text',
            'options' => [
                'label' => 'Naam',
            ],
        ]);

        $this->add([
            'name' => 'submit_save',
            'type' => 'submit',
            'attributes' => [
                'label' => 'Opslaan',
                'value' => 'Opslaan',
            ],
        ]);
    }

    public function getInputFilterSpecification(): array
    {
        $filter = parent::getInputFilterSpecification();
        $filter += [
            'name' => [
                'required' => true,
            ],
        ];

        return $filter;
    }
}
