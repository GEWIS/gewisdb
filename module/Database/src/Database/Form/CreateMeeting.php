<?php

namespace Database\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Database\Model\Meeting;

class CreateMeeting extends Form
{
    public function __construct()
    {
        parent::__construct();

        $this->add([
            'name' => 'type',
            'type' => 'select',
            'options' => [
                'label' => 'Vergadertype',
                'value_options' => [
                    'BV' => 'BV (Bestuursvergadering)',
                    'AV' => 'AV (Algemene Ledenvergadering)',
                    'VV' => 'VV (Voorzittersvergadering)',
                    'Virt' => 'Virt (Virtuele vergadering)'
                ]
            ]
        ]);

        $this->add([
            'name' => 'number',
            'type' => 'text',
            'options' => [
                'label' => 'Vergadernummer'
            ]
        ]);

        $this->add([
            'name' => 'date',
            'type' => 'date',
            'options' => [
                'label' => 'Vergaderdatum'
            ]
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Verzend'
            ]
        ]);

        $this->initFilters();
    }

    protected function initFilters()
    {
        $filter = new InputFilter();

        $filter->add([
            'name' => 'type',
            'required' => true,
            'validators' => [
                [
                    'name' => 'in_array',
                    'options' => [
                        'haystack' => Meeting::getTypes()
                    ]
                ]
            ]
        ]);

        $filter->add([
            'name' => 'number',
            'required' => true,
            'validators' => [
                ['name' => 'digits'],
                [
                    'name' => 'LessThan',
                    'options' => [
                        'max' => 100000
                    ]
                ]
            ]
        ]);

        $filter->add([
            'name' => 'number',
            'required' => true,
            'validators' => [
                ['name' => 'digits']
            ]
        ]);

        $filter->add([
            'name' => 'date',
            'required' => true,
            'validators' => [
                ['name' => 'date']
            ]
        ]);

        $this->setInputFilter($filter);
    }
}
