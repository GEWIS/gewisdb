<?php

namespace Database\Form;

use Laminas\Form\Form;
use Laminas\InputFilter\InputFilter;
use Laminas\InputFilter\InputFilterProviderInterface;

class Query extends Form implements InputFilterProviderInterface
{
    public function __construct()
    {
        parent::__construct();

        $this->add([
            'name' => 'query',
            'type' => 'textarea',
            'options' => [
                'label' => 'Query input',
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Uitvoeren',
            ],
            'options' => [
                'label' => 'Uitvoeren',
            ],
        ]);
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'query' => [
                'required' => true,
            ],
        ];
    }
}
