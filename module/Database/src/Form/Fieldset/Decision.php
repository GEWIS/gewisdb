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

        $this->add([
            'name' => 'meeting_type',
            'type' => 'hidden',
        ]);

        $this->add([
            'name' => 'meeting_number',
            'type' => 'hidden',
        ]);

        $this->add([
            'name' => 'point',
            'type' => 'hidden',
        ]);

        $this->add([
            'name' => 'number',
            'type' => 'hidden',
        ]);
    }

    /**
     * Specification for input filters.
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'meeting_type' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => InArray::class,
                        'options' => [
                            'haystack' => MeetingTypes::values(),
                        ],
                    ],
                ],
            ],
            'meeting_number' => [
                'required' => true,
                'validators' => [
                    ['name' => Digits::class],
                ],
            ],
            'point' => [
                'required' => true,
                'validators' => [
                    ['name' => Digits::class],
                ],
            ],
            'number' => [
                'required' => true,
                'validators' => [
                    ['name' => Digits::class],
                ],
            ],
        ];
    }
}
