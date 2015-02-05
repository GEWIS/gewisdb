<?php

namespace Database\Form\Board;

use Database\Form\AbstractDecision;
use Database\Form\Fieldset;

use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterProviderInterface;

class Install extends AbstractDecision
    implements InputFilterProviderInterface
{

    public function __construct(Fieldset\Meeting $meeting, Fieldset\Member $member)
    {
        parent::__construct($meeting);

        $this->add(clone $member);

        $this->add(array(
            'name' => 'function',
            'type' => 'text',
            'options' => array(
                'label' => 'Functie',
            )
        ));

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => 'Installeer als bestuurder'
            )
        ));
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification()
    {
        return array(
            'function' => array(
                'required' => true,
                'validators' => array(
                    array(
                        'name' => 'string_length',
                        'options' => array(
                            'min' => 2,
                            'max' => 32
                        )
                    )
                )
            ),
        );
    }
}
