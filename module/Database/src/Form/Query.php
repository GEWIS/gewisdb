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

        $this->add(array(
            'name' => 'query',
            'type' => 'textarea',
            'options' => array(
                'label' => 'Query input'
            )
        ));

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => 'Uitvoeren'
            )
        ));
        $this->get('submit')->setLabel('Uitvoeren');
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification(): array
    {
        return array();
    }
}
