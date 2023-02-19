<?php

namespace Database\Form;

use Laminas\Filter\StringToLower;
use Laminas\Form\Element\{
    Submit,
    Text,
};
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\{
    Regex,
    StringLength,
};

class Address extends Form implements InputFilterProviderInterface
{
    public function __construct(private readonly Translator $translator)
    {
        parent::__construct();

        $this->add([
            'name' => 'country',
            'type' => Text::class,
            'options' => [
                'label' => $this->translator->translate('Country'),
                'value' => 'NETHERLANDS',
            ],
        ]);

        $this->add([
            'name' => 'street',
            'type' => Text::class,
            'options' => [
                'label' => $this->translator->translate('Street'),
            ],
        ]);

        $this->add([
            'name' => 'number',
            'type' => Text::class,
            'options' => [
                'label' => $this->translator->translate('House Number'),
            ],
        ]);

        $this->add([
            'name' => 'postalCode',
            'type' => Text::class,
            'options' => [
                'label' => $this->translator->translate('Postal Code'),
            ],
        ]);

        $this->add([
            'name' => 'city',
            'type' => Text::class,
            'options' => [
                'label' => $this->translator->translate('City'),
            ],
        ]);

        $this->add([
            'name' => 'phone',
            'type' => Text::class,
            'options' => [
                'label' => $this->translator->translate('Phone Number'),
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => $this->translator->translate('Update Address'),
            ],
        ]);
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'country' => [
                'required' => true,
                'filters' => [
                    ['name' => StringToLower::class],
                ],
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 2,
                            'max' => 32,
                        ],
                    ],
                ],
            ],
            'street' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 2,
                            'max' => 32,
                        ],
                    ],
                ],
            ],
            'number' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => Regex::class,
                        'options' => [
                            'pattern' => '/^[0-9]+[a-zA-Z]*/',
                        ],
                    ],
                ],
            ],
            'postalCode' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 2,
                            'max' => 16,
                        ],
                    ],
                ],
            ],
            'city' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 2,
                            'max' => 32,
                        ],
                    ],
                ],
            ],
            // TODO: phone number validation
        ];
    }
}
