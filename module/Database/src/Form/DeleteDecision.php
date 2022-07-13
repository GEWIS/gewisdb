<?php

namespace Database\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilterProviderInterface;

class DeleteDecision extends Form implements InputFilterProviderInterface
{
    public function __construct()
    {
        parent::__construct();

        $this->add(array(
            'name' => 'submit_yes',
            'type' => 'submit',
            'attributes' => array(
                'value' => 'Ja'
            )
        ));

        $this->add(array(
            'name' => 'submit_no',
            'type' => 'submit',
            'attributes' => array(
                'value' => 'Nee'
            )
        ));
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
