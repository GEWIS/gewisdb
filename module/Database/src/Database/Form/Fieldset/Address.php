<?php

namespace Database\Form\Fieldset;

use Zend\Form\Fieldset;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\I18n\Translator\TranslatorInterface as Translator;
use Database\Model\Address as AddressModel;

class Address extends Fieldset implements InputFilterProviderInterface
{
    public function __construct(Translator $translator)
    {
        parent::__construct('address');

        $this->add([
            'name' => 'type',
            'type' => 'hidden'
        ]);

        $this->add([
            'name' => 'country',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('Land')
            ],
        ]);
        $this->get('country')->setValue('netherlands');

        $this->add([
            'name' => 'street',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('Straat')
            ]
        ]);

        $this->add([
            'name' => 'number',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('Huisnummer')
            ]
        ]);

        $this->add([
            'name' => 'postalCode',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('Postcode')
            ]
        ]);

        $this->add([
            'name' => 'city',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('Stad')
            ]
        ]);

        $this->add([
            'name' => 'phone',
            'type' => 'text',
            'options' => [
                'label' => $translator->translate('Telefoonnummer')
            ]
        ]);

        // TODO: filters
    }

    /**
     * Specification for input filters.
     */
    public function getInputFilterSpecification()
    {
        return [
            'type' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'in_array',
                        'options' => [
                            'haystack' => AddressModel::getTypes()
                        ]
                    ]
                ]
            ],
            'country' => [
                'required' => true,
                'filters' => [
                    ['name' => 'string_to_lower']
                ],
                'validators' => [
                    [
                        'name' => 'string_length',
                        'options' => [
                            'min' => 2,
                            'max' => 32
                        ]
                    ]
                ]
            ],
            'street' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'string_length',
                        'options' => [
                            'min' => 1,
                            'max' => 32
                        ]
                    ]
                ]
            ],
            'number' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'regex',
                        'options' => [
                            'pattern' => '/^[0-9]+[a-zA-Z]*/'
                        ]
                    ]
                ]
            ],
            'postalCode' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'string_length',
                        'options' => [
                            'min' => 2,
                            'max' => 16
                        ]
                    ]
                ]
            ],
            'city' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => 'string_length',
                        'options' => [
                            'min' => 1,
                            'max' => 32
                        ]
                    ]
                ]
            ]
        ];
    }
}
