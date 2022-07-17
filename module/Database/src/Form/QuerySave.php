<?php

namespace Database\Form;

use Laminas\Form\Element\{
    Submit,
    Text,
};
use Laminas\InputFilter\InputFilterProviderInterface;

class QuerySave extends Query implements InputFilterProviderInterface
{
    public function __construct()
    {
        parent::__construct();

        $this->add([
            'name' => 'name',
            'type' => Text::class,
            'options' => [
                'label' => 'Naam',
            ],
        ]);

        $this->add([
            'name' => 'submit_save',
            'type' => Submit::class,
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
