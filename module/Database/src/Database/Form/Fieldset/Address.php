<?php

namespace Database\Form\Fieldset;

use Zend\Form\Fieldset;
use Zend\InputFilter\InputFilterProviderInterface;

class Address extends Fieldset
    implements InputFilterProviderInterface
{

    public function __construct()
    {
        parent::__construct('address');

        $this->add(array(
            'name' => 'type',
            'type' => 'hidden'
        ));

        $this->add(array(
            'name' => 'country',
            'type' => 'text',
            'options' => array(
                'label' => 'Land',
                'value' => 'netherlands'
            )
        ));

        $this->add(array(
            'name' => 'street',
            'type' => 'text',
            'options' => array(
                'label' => 'Straat'
            )
        ));

        $this->add(array(
            'name' => 'number',
            'type' => 'text',
            'options' => array(
                'label' => 'Huisnummer'
            )
        ));

        $this->add(array(
            'name' => 'postalCode',
            'type' => 'text',
            'options' => array(
                'label' => 'Postcode'
            )
        ));

        $this->add(array(
            'name' => 'City',
            'type' => 'text',
            'options' => array(
                'label' => 'Stad'
            )
        ));

        $this->add(array(
            'name' => 'phone',
            'type' => 'text',
            'options' => array(
                'label' => 'Telefoonnummer'
            )
        ));

        // TODO: filters
    }

    /**
     * Specification for input filters.
     */
    public function getInputFilterSpecification()
    {
        // TODO: add filter specification
        return array();
    }
}
