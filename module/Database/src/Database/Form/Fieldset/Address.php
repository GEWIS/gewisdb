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
    public function getInputFilterSpecification()
    {
        return array(
            'type' => array(
                'required' => true,
                'validators' => array(
                    array(
                        'name' => 'in_array',
                        'options' => array(
                            'haystack' => AddressModel::getTypes()
                        )
                    )
                )
            ),
            'country' => array(
                'required' => true,
                'filters' => array(
                    array('name' => 'string_to_lower')
                ),
                'validators' => array(
                    array(
                        'name' => 'string_length',
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
                        'name' => 'string_length',
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
                        'name' => 'regex',
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
                        'name' => 'string_length',
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
                        'name' => 'string_length',
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
