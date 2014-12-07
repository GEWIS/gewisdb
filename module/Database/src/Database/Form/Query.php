<?php

namespace Database\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterProviderInterface;

class Query extends Form
    implements InputFilterProviderInterface
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
                'value' => 'Execute'
            )
        ));
        $this->get('submit')->setLabel('Execute');
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
