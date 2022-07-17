<?php

namespace Database\Form;

use Laminas\Form\Element\Submit;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;

class DeleteAddress extends Form implements InputFilterProviderInterface
{
    public function __construct()
    {
        parent::__construct();

        $this->add([
            'name' => 'submit_yes',
            'type' => Submit::class,
            'attributes' => [
                'value' => 'Ja',
            ],
        ]);

        $this->add([
            'name' => 'submit_no',
            'type' => Submit::class,
            'attributes' => [
                'value' => 'Nee',
            ],
        ]);
    }

    public function getInputFilterSpecification(): array
    {
        return [
            'submit_yes' => [
                'required' => true,
            ],
        ];
    }
}
