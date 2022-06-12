<?php

namespace Database\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilterProviderInterface;

class MemberExpiration extends Form implements InputFilterProviderInterface
{
    public function __construct()
    {
        parent::__construct();

        $this->add([
            'name' => 'submit_yes',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Ja'
            ]
        ]);

        $this->add([
            'name' => 'submit_no',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Nee'
            ]
        ]);
    }

    public function getInputFilterSpecification()
    {
        return [
            'submit_yes' => [
                'required' => true
            ]
        ];
    }
}
