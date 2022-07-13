<?php

namespace Database\Form;

use Database\Model\Meeting;
use Zend\Form\Form;
use Zend\InputFilter\InputFilter;

class CreateMeeting extends Form
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

        $this->initFilters();
    }

    protected function initFilters()
    {
        $filter = new InputFilter();

        $filter->add(array(
            'name' => 'type',
            'required' => true,
            'validators' => array(
                array(
                    'name' => 'in_array',
                    'options' => array(
                        'haystack' => Meeting::getTypes()
                    )
                )
            )
        ));

        $filter->add(array(
            'name' => 'number',
            'required' => true,
            'validators' => array(
                array('name' => 'digits'),
                array(
                    'name' => 'LessThan',
                    'options' => array(
                        'max' => 100000
                    )
                )
            )
        ));

        $filter->add(array(
            'name' => 'number',
            'required' => true,
            'validators' => array(
                array('name' => 'digits')
            )
        ));

        $filter->add(array(
            'name' => 'date',
            'required' => true,
            'validators' => array(
                array('name' => 'date')
            )
        ));

        $this->setInputFilter($filter);
    }
}
