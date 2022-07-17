<?php

namespace Database\Form;

use Laminas\Form\Element\{
    Submit,
    Textarea,
};
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;

class Query extends Form implements InputFilterProviderInterface
{
    public function __construct()
    {
        parent::__construct();

        $this->add([
            'name' => 'query',
            'type' => Textarea::class,
            'options' => [
                'label' => 'Query input',
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
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
