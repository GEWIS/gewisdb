<?php

namespace Database\Form\Fieldset;

use Application\Model\Enums\MeetingTypes;
use Database\Model\Decision as DecisionModel;
use Database\Model\Meeting as MeetingModel;
use Laminas\Form\Fieldset;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\Digits;
use Laminas\Validator\InArray;

class Decision extends Fieldset implements InputFilterProviderInterface
{
    public function __construct()
    {
        parent::__construct('fdecision');

        $this->add(array(
            'name' => 'meeting_type',
            'type' => 'hidden'
        ));

        $this->add(array(
            'name' => 'meeting_number',
            'type' => 'hidden'
        ));

        $this->add(array(
            'name' => 'point',
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
                            'haystack' => MeetingTypes::values(),
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
            'point' => array(
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
