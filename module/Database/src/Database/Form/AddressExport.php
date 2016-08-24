<?php

namespace Database\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterProviderInterface;

class AddressExport extends Form
    implements InputFilterProviderInterface
{

    public function __construct()
    {
        parent::__construct();

        $this->add([
            'name' => 'supremum',
            'type' => 'checkbox'
        ]);

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => 'Exporteer'
            )
        ));

        $this->get('submit')->setAttribute('value', 'Exporteer');
        $this->get('submit')->setLabel('Exporteer');
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification()
    {
        return [];
    }
}
