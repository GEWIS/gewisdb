<?php

namespace Database\Form\Fieldset;

use Database\Model\SubDecision as SubDecisionModel;
use Database\Model\Meeting as MeetingModel;
use Zend\Form\Fieldset;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\Validator\Digits;
use Zend\Validator\InArray;

class SubDecision extends Fieldset implements InputFilterProviderInterface
{
    public function __construct()
    {
        parent::__construct('subdecision');

        $this->add(array(
            'name' => 'meeting_type',
            'type' => 'hidden'
        ));

        $this->add(array(
            'name' => 'meeting_number',
            'type' => 'hidden'
        ));

        $this->add(array(
            'name' => 'decision_point',
            'type' => 'hidden'
        ));

        $this->add(array(
            'name' => 'decision_number',
            'type' => 'hidden'
        ));

        $this->add(array(
            'name' => 'number',
            'type' => 'hidden'
        ));
    }

    /**
     * Specification for input filters.
     */
    public function getInputFilterSpecification(): array
    {
        return array(
            'meeting_type' => array(
                'required' => true,
                'validators' => array(
                    array(
                        'name' => InArray::class,
                        'options' => array(
                            'haystack' => MeetingModel::getTypes()
                        )
                    )
                )
            ),
            'meeting_number' => array(
                'required' => true,
                'validators' => array(
                    array('name' => Digits::class)
                )
            ),
            'decision_point' => array(
                'required' => true,
                'validators' => array(
                    array('name' => Digits::class)
                )
            ),
            'decision_number' => array(
                'required' => true,
                'validators' => array(
                    array('name' => Digits::class)
                )
            ),
            'number' => array(
                'required' => true,
                'validators' => array(
                    array('name' => Digits::class)
                )
            ),
        );
    }
}
