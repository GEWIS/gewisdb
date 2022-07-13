<?php

namespace Database\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\Validator\StringLength;

class MailingList extends Form implements InputFilterProviderInterface
{
    public function __construct()
    {
        parent::__construct();

        $this->add(array(
            'name' => 'name',
            'type' => 'text',
            'options' => array(
                'label' => 'Naam'
            )
        ));

        $this->add(array(
            'name' => 'description',
            'type' => 'textarea',
            'options' => array(
                'label' => 'Beschrijving (nederlands)'
            )
        ));

        $this->add(array(
            'name' => 'enDescription',
            'type' => 'textarea',
            'options' => array(
                'label' => 'Beschrijving (engels)'
            )
        ));

        $this->add(array(
            'name' => 'onForm',
            'type' => 'checkbox',
            'options' => array(
                'label' => 'Op inschrijfformulier'
            )
        ));
        $this->get('onForm')->setChecked(true);

        $this->add(array(
            'name' => 'defaultSub',
            'type' => 'checkbox',
            'options' => array(
                'label' => 'Standaard ingeschreven'
            )
        ));

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => 'Voeg lijst toe'
            )
        ));
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification(): array
    {
        return array(
            'name' => array(
                'required' => true,
                'validators' => array(
                    array(
                        'name' => StringLength::class,
                        'options' => array(
                            'min' => 2,
                            'max' => 64
                        )
                    )
                )
            ),
            'description' => array(
                'required' => true,
                'validators' => array(
                    array(
                        'name' => StringLength::class,
                        'options' => array(
                            'min' => 10
                        )
                    )
                )
            ),
            'enDescription' => array(
                'required' => true,
                'validators' => array(
                    array(
                        'name' => StringLength::class,
                        'options' => array(
                            'min' => 10
                        )
                    )
                )
            ),
        );
    }
}
