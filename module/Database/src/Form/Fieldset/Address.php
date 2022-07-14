<?php

namespace Database\Form\Fieldset;

use Database\Model\Address as AddressModel;
use Laminas\Filter\StringToLower;
use Laminas\Form\Fieldset;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\I18n\Translator\TranslatorInterface as Translator;
use Laminas\Validator\InArray;
use Laminas\Validator\Regex;
use Laminas\Validator\StringLength;

class Address extends Fieldset implements InputFilterProviderInterface
{
    public function __construct(Translator $translator)
    {
        parent::__construct('address');

        $this->add(array(
            'name' => 'type',
            'type' => 'hidden'
        ));

        $this->add(array(
            'name' => 'country',
            'type' => 'text',
            'options' => array(
                'label' => $translator->translate('Land')
            ),
        ));
        $this->get('country')->setValue('netherlands');

        $this->add(array(
            'name' => 'street',
            'type' => 'text',
            'options' => array(
                'label' => $translator->translate('Straat')
            )
        ));

        $this->add(array(
            'name' => 'number',
            'type' => 'text',
            'options' => array(
                'label' => $translator->translate('Huisnummer')
            )
        ));

        $this->add(array(
            'name' => 'postalCode',
            'type' => 'text',
            'options' => array(
                'label' => $translator->translate('Postcode')
            )
        ));

        $this->add(array(
            'name' => 'city',
            'type' => 'text',
            'options' => array(
                'label' => $translator->translate('Stad')
            )
        ));

        $this->add(array(
            'name' => 'phone',
            'type' => 'text',
            'options' => array(
                'label' => $translator->translate('Telefoonnummer')
            )
        ));

        // TODO: filters
    }

    /**
     * Specification for input filters.
     */
    public function getInputFilterSpecification(): array
    {
        return array(
            'type' => array(
                'required' => true,
                'validators' => array(
                    array(
                        'name' => InArray::class,
                        'options' => array(
                            'haystack' => AddressModel::getTypes()
                        )
                    )
                )
            ),
            'country' => array(
                'required' => true,
                'filters' => array(
                    array('name' => StringToLower::class)
                ),
                'validators' => array(
                    array(
                        'name' => StringLength::class,
                        'options' => array(
                            'min' => 2,
                            'max' => 32
                        )
                    )
                )
            ),
            'street' => array(
                'required' => true,
                'validators' => array(
                    array(
                        'name' => StringLength::class,
                        'options' => array(
                            'min' => 1,
                            'max' => 32
                        )
                    )
                )
            ),
            'number' => array(
                'required' => true,
                'validators' => array(
                    array(
                        'name' => Regex::class,
                        'options' => array(
                            'pattern' => '/^[0-9]+[a-zA-Z]*/'
                        )
                    )
                )
            ),
            'postalCode' => array(
                'required' => true,
                'validators' => array(
                    array(
                        'name' => StringLength::class,
                        'options' => array(
                            'min' => 2,
                            'max' => 16
                        )
                    )
                )
            ),
            'city' => array(
                'required' => true,
                'validators' => array(
                    array(
                        'name' => StringLength::class,
                        'options' => array(
                            'min' => 1,
                            'max' => 32
                        )
                    )
                )
            )
        );
    }
}
