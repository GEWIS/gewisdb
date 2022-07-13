<?php

namespace Database\Form\Fieldset;

use Database\Model\SubDecision\Installation as InstallationModel;
use Zend\Form\Fieldset;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\Validator\StringLength;

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

    public function getInputFilterSpecification(): array
    {
        return array(
            'function' => array(
                'required' => true,
                'validators' => array(
                    array(
                        'name' => StringLength::class,
                        'options' => array(
                            'min' => 2
                        )
                    )
                )
            )
        );
    }
}
