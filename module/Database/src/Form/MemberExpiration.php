<?php

namespace Database\Form;

use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;

class MemberExpiration extends Form implements InputFilterProviderInterface
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
        return array(
            'submit_yes' => array(
                'required' => true
            )
        );
    }
}
