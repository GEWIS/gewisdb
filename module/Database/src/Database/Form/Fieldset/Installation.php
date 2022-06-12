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

        $this->add(array(
            'name' => 'function',
            'type' => 'hidden'
        ));
    }

    public function getInputFilterSpecification()
    {
        return array(
            'function' => array(
                'required' => true,
                'validators' => array(
                    array(
                        'name' => 'string_length',
                        'options' => array(
                            'min' => 2
                        )
                    )
                )
            )
        );
    }
}
