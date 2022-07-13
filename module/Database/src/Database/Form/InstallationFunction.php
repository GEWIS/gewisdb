<?php

namespace Database\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterProviderInterface;

class InstallationFunction extends Form implements InputFilterProviderInterface
{
    public function __construct()
    {
        parent::__construct();

        $this->add(array(
            'name' => 'name',
            'type' => 'text',
            'options' => array(
                'label' => 'Functienaam'
            )
        ));

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => 'Maak functie'
            )
        ));
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification()
    {
        return array(
        );
    }
}
