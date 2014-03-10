<?php

namespace Database\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;

class Budget extends Form
{

    public function __construct()
    {
        parent::__construct();

        $this->add(array(
            'name' => 'type',
            'type' => 'select',
            'options' => array(
                'label' => 'Begroting / Afrekening',
                'value_options' => array(
                    'budget' => 'Begroting',
                    'reckoning' => 'Afrekening'
                )
            )
        ));

        $this->add(array(
            'name' => 'name',
            'type' => 'text',
            'options' => array(
                'label' => 'Naam',
            )
        ));

        $this->add(array(
            'name' => 'date',
            'type' => 'date',
            'options' => array(
                'label' => 'Datum begroting / afrekening'
            )
        ));

        $this->add(array(
            'name' => 'author',
            'type' => 'text',
            'options' => array(
                'label' => 'Auteur'
            )
        ));

        $this->add(array(
            'name' => 'version',
            'type' => 'text',
            'options' => array(
                'label' => 'Versie'
            )
        ));

        $this->add(array(
            'name' => 'organ',
            'type' => 'text',
            'options' => array(
                'label' => 'Orgaan (optioneel)'
            )
        ));

        $this->add(array(
            'name' => 'approve',
            'type' => 'radio',
            'options' => array(
                'label' => 'Goedkeuren / Afkeuren',
                'value_options' => array(
                    'approve' => 'Goedkeuren',
                    'disapprove' => 'Afkeuren'
                )
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

        $this->setInputFilter($filter);
    }
}
