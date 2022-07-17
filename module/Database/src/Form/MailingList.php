<?php

namespace Database\Form;

use Laminas\Form\Element\{
    Checkbox,
    Submit,
    Text,
    Textarea,
};
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\StringLength;

class MailingList extends Form implements InputFilterProviderInterface
{
    public function __construct()
    {
        parent::__construct();

        $this->add([
            'name' => 'name',
            'type' => Text::class,
            'options' => [
                'label' => 'Naam',
            ],
        ]);

        $this->add([
            'name' => 'nl_description',
            'type' => Textarea::class,
            'options' => [
                'label' => 'Beschrijving (nederlands)',
            ],
        ]);

        $this->add([
            'name' => 'en_description',
            'type' => Textarea::class,
            'options' => [
                'label' => 'Beschrijving (engels)',
            ],
        ]);

        $this->add([
            'name' => 'onForm',
            'type' => Checkbox::class,
            'options' => [
                'label' => 'Op inschrijfformulier',
            ],
        ]);
        $this->get('onForm')->setChecked(true);

        $this->add([
            'name' => 'defaultSub',
            'type' => Checkbox::class,
            'options' => [
                'label' => 'Standaard ingeschreven',
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => 'Voeg lijst toe',
            ],
        ]);
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'name' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 2,
                            'max' => 64,
                        ],
                    ],
                ],
            ],
            'nl_description' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 10,
                        ],
                    ],
                ],
            ],
            'en_description' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 10,
                        ],
                    ],
                ],
            ],
        ];
    }
}
