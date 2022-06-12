<?php

namespace Database\Form\Fieldset;

use Zend\Form\Fieldset;
use Zend\InputFilter\InputFilterProviderInterface;

class Member extends Fieldset implements InputFilterProviderInterface
{
    public function __construct()
    {
        parent::__construct('member');

        // is only there for fun, will only be used as source for 'lidnr' autocomplete
        // which uses AJAX to find members
        $this->add([
            'name' => 'name',
            'type' => 'text',
            'options' => [
                'label' => 'Lid'
            ]
        ]);

        // actual way to find the member
        $this->add([
            'name' => 'lidnr',
            'type' => 'hidden',
        ]);
    }

    public function getInputFilterSpecification()
    {
        return [
            'lidnr' => [
                'required' => true,
                'validators' => [
                    ['name' => 'digits']
                ]
            ]
        ];
    }
}
