<?php

namespace Database\Form\Fieldset;

use Zend\Form\Fieldset;
use Zend\InputFilter\InputFilterProviderInterface;
use Database\Model\SubDecision\Installation as InstallationModel;

class Installation extends Fieldset implements InputFilterProviderInterface
{
    public function __construct(Member $member)
    {
        parent::__construct('installation');

        $member->remove('name');
        $this->add($member);

        $this->add([
            'name' => 'function',
            'type' => 'hidden'
        ]);
    }

    public function getInputFilterSpecification()
    {
        return [
            'function' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'string_length',
                        'options' => [
                            'min' => 2
                        ]
                    ]
                ]
            ]
        ];
    }
}
