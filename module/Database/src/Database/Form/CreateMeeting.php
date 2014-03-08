<?php

namespace Database\Form;

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
                    'bv' => 'BV (Bestuursvergadering)',
                    'av' => 'AV (Algemene Ledenvergadering)',
                    'vv' => 'VV (Voorzittersvergadering)',
                    'virt' => 'Virt (Virtuele vergadering)'
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
        /*
        $filter = new InputFilter();

        $filter->add(array(
            'name' => 'course',
            'required' => true,
            'validators' => array(
                array(
                    'name' => 'string_length',
                    'options' => array(
                        'min' => 5,
                        'max' => 6
                    )
                ),
                array('name' => 'alnum')
            ),
            'filters' => array(
                array('name' => 'string_to_upper')
            )
        ));

        $filter->add(array(
            'name' => 'date',
            'required' => true,
            'validators' => array(
                array('name' => 'date')
            )
        ));

        $filter->add(array(
            'name' => 'notes',
            'required' => true,
            'validators' => array(
                array(
                    'name' => 'File\Extension',
                    'options' => array(
                        'extension' => 'pdf'
                    )
                ),
                array(
                    'name' => 'File\MimeType',
                    'options' => array(
                        'mimeType' => 'application/pdf'
                    )
                )
            )
        ));

        $this->setInputFilter($filter);
         */
    }
}
