<?php

namespace Database\Form\Board;

use Database\Form\AbstractDecision;
use Database\Form\Fieldset;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\StringLength;

class Install extends AbstractDecision implements InputFilterProviderInterface
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
            'name' => 'date',
            'type' => 'date',
            'options' => array(
                'label' => 'Van kracht per',
            )
        ));

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => 'Installeer bestuurder'
            )
        ));
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification(): array
    {
        return array(
            'function' => array(
                'required' => true,
                'validators' => array(
                    array(
                        'name' => StringLength::class,
                        'options' => array(
                            'min' => 2,
                            'max' => 32
                        )
                    )
                )
            ),
            'date' => array(
                'required' => true
            )
        );
    }
}
