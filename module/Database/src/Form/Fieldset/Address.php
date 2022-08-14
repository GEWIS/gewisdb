<?php

namespace Database\Form\Fieldset;

use Application\Model\Enums\AddressTypes;
use Laminas\Filter\StringToLower;
use Laminas\Form\Element\{
    Hidden,
    Text,
};
use Laminas\Filter\StringTrim;
use Laminas\Form\Fieldset;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\Validator\{
    InArray,
    Regex,
    StringLength,
};

class Address extends Fieldset implements InputFilterProviderInterface
{
    public function __construct(MvcTranslator $translator)
    {
        parent::__construct('address');

        $this->add([
            'name' => 'type',
            'type' => Hidden::class,
        ]);

        $this->add([
            'name' => 'country',
            'type' => Text::class,
            'options' => [
                'label' => $translator->translate('Land'),
            ],
        ]);
        $this->get('country')->setValue('netherlands');

        $this->add([
            'name' => 'street',
            'type' => Text::class,
            'options' => [
                'label' => $translator->translate('Straat'),
            ],
        ]);

        $this->add([
            'name' => 'number',
            'type' => Text::class,
            'options' => [
                'label' => $translator->translate('Huisnummer'),
            ],
        ]);

        $this->add([
            'name' => 'postalCode',
            'type' => Text::class,
            'options' => [
                'label' => $translator->translate('Postcode'),
            ],
        ]);

        $this->add([
            'name' => 'city',
            'type' => Text::class,
            'options' => [
                'label' => $translator->translate('Stad'),
            ],
        ]);

        $this->add([
            'name' => 'phone',
            'type' => Text::class,
            'options' => [
                'label' => $translator->translate('Telefoonnummer'),
            ],
        ]);

        // TODO: filters
    }

    /**
     * Specification for input filters.
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'type' => [
                'required' => true,
                'filters' => [
                    ['name' => StringTrim::class],
                ],
                'validators' => [
                    [
                        'name' => InArray::class,
                        'options' => [
                            'haystack' => AddressTypes::values(),
                        ],
                    ],
                ],
            ],
            'country' => [
                'required' => true,
                'filters' => [
                    ['name' => StringToLower::class],
                    ['name' => StringTrim::class],
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
                'filters' => [
                    ['name' => StringTrim::class],
                ],
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 1,
                            'max' => 32,
                        ],
                    ],
                ],
            ],
            'number' => [
                'required' => true,
                'filters' => [
                    ['name' => StringTrim::class],
                ],
                'validators' => [
                    [
                        'name' => Regex::class,
                        'options' => [
                            'pattern' => '/^[0-9]+[a-z-A-Z\ \d\#\-\.\/]*$/',
                        ],
                    ],
                ],
            ],
            'postalCode' => [
                'required' => true,
                'filters' => [
                    ['name' => StringTrim::class],
                ],
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
                'filters' => [
                    ['name' => StringTrim::class],
                ],
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 1,
                            'max' => 32,
                        ],
                    ],
                ],
            ],
        ];
    }
}
