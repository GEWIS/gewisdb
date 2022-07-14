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

        $this->add(array(
            'name' => 'type',
            'type' => 'select',
            'options' => array(
                'label' => 'Vergadertype',
                'value_options' => array(
                    'BV' => 'BV (Bestuursvergadering)',
                    'AV' => 'AV (Algemene Ledenvergadering)',
                    'VV' => 'VV (Voorzittersvergadering)',
                    'Virt' => 'Virt (Virtuele vergadering)'
                )
            )
        ));

        $this->add(array(
            'name' => 'number',
            'type' => 'text',
            'options' => array(
                'label' => 'Vergadernummer'
            )
        ));

        $this->add(array(
            'name' => 'date',
            'type' => 'date',
            'options' => array(
                'label' => 'Vergaderdatum'
            )
        ));

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => 'Verzend'
            )
        ));
    }

    public function getInputFilterSpecification(): array
    {
        return [
            'type' => array(
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
            'number' => array(
                'required' => true,
                'validators' => array(
                    array('name' => Digits::class),
                    array(
                        'name' => LessThan::class,
                        'options' => array(
                            'max' => 100000
                        )
                    )
                )
            ),
            'date' => array(
                'required' => true,
                'validators' => array(
                    array('name' => Date::class)
                )
            ),
        ];
    }
}
