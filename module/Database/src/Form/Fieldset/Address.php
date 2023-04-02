<?php

namespace Database\Form\Fieldset;

use Application\Model\Enums\{
    AddressTypes,
    PostalRegions,
};
use Laminas\Form\Element\{
    Hidden,
    Select,
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
            'type' => Select::class,
            'options' => [
                'label' => $translator->translate('Postal Region'),
                'empty_option' => $translator->translate('Select Postal Region'),
                'value_options' => PostalRegions::formValues(),
            ],
        ]);
        $this->get('country')->setValue(PostalRegions::Netherlands->value);

        $this->add([
            'name' => 'street',
            'type' => Text::class,
            'options' => [
                'label' => $translator->translate('Street'),
            ],
        ]);

        $this->add([
            'name' => 'number',
            'type' => Text::class,
            'options' => [
                'label' => $translator->translate('House Number'),
            ],
        ]);

        $this->add([
            'name' => 'postalCode',
            'type' => Text::class,
            'options' => [
                'label' => $translator->translate('Postal Code'),
            ],
        ]);

        $this->add([
            'name' => 'city',
            'type' => Text::class,
            'options' => [
                'label' => $translator->translate('City'),
            ],
        ]);

        $this->add([
            'name' => 'phone',
            'type' => Text::class,
            'options' => [
                'label' => $translator->translate('Phone Number'),
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
                'validators' => [
                    [
                        'name' => InArray::class,
                        'options' => [
                            'haystack' => PostalRegions::values(),
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
