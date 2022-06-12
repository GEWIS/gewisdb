<?php

namespace Database\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterProviderInterface;

class Query extends Form implements InputFilterProviderInterface
{
    public function __construct()
    {
        parent::__construct();

        $this->add([
            'name' => 'query',
            'type' => 'textarea',
            'options' => [
                'label' => 'Query input'
            ]
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Uitvoeren'
            ]
        ]);
        $this->get('submit')->setLabel('Uitvoeren');
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification()
    {
        return [
        ];
    }
}
