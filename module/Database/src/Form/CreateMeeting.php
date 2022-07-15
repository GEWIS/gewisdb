<?php

namespace Database\Form;

use Application\Model\Enums\MeetingTypes;
use Database\Model\Meeting;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\Date;
use Laminas\Validator\Digits;
use Laminas\Validator\InArray;
use Laminas\Validator\LessThan;

class CreateMeeting extends Form implements InputFilterProviderInterface
{
    public function __construct()
    {
        parent::__construct();

        $this->add([
            'name' => 'type',
            'type' => 'select',
            'options' => [
                'label' => 'Vergadertype',
                'value_options' => [
                    'BV' => 'BV (Bestuursvergadering)',
                    'AV' => 'AV (Algemene Ledenvergadering)',
                    'VV' => 'VV (Voorzittersvergadering)',
                    'Virt' => 'Virt (Virtuele vergadering)',
                ],
            ],
        ]);

        $this->add([
            'name' => 'number',
            'type' => 'text',
            'options' => [
                'label' => 'Vergadernummer',
            ],
        ]);

        $this->add([
            'name' => 'date',
            'type' => 'date',
            'options' => [
                'label' => 'Vergaderdatum',
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Verzend',
            ],
        ]);
    }

    public function getInputFilterSpecification(): array
    {
        return [
            'type' => [
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
            'number' => [
                'required' => true,
                'validators' => [
                    ['name' => Digits::class],
                    [
                        'name' => LessThan::class,
                        'options' => [
                            'max' => 100000,
                        ],
                    ],
                ],
            ],
            'date' => [
                'required' => true,
                'validators' => [
                    ['name' => Date::class],
                ],
            ],
        ];
    }
}
