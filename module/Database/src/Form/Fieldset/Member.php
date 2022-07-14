<?php

namespace Database\Form\Fieldset;

use Laminas\Form\Fieldset;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\Digits;

class Member extends Fieldset implements InputFilterProviderInterface
{
    public function __construct()
    {
        parent::__construct('member');

        // is only there for fun, will only be used as source for 'lidnr' autocomplete
        // which uses AJAX to find members
        $this->add(array(
            'name' => 'name',
            'type' => 'text',
            'options' => array(
                'label' => 'Lid'
            )
        ));

        // actual way to find the member
        $this->add(array(
            'name' => 'lidnr',
            'type' => 'hidden',
        ));
    }

    public function getInputFilterSpecification(): array
    {
        return array(
            'lidnr' => array(
                'required' => true,
                'validators' => array(
                    array('name' => Digits::class)
                )
            )
        );
    }
}
