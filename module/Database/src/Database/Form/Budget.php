<?php

namespace Database\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Database\Model\SubDecision;

class Budget extends Form
{

    public function __construct(Fieldset\Member $member)
    {
        parent::__construct();

        $this->add(array(
            'name' => 'type',
            'type' => 'select',
            'options' => array(
                'label' => 'Begroting / Afrekening',
                'value_options' => array(
                    SubDecision::TYPE_BUDGET => 'Begroting',
                    SubDecision::TYPE_RECKONING => 'Afrekening'
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

        $member->setName('author');
        $member->setLabel('Auteur');
        $this->add($member);

        $this->add(array(
            'name' => 'version',
            'type' => 'text',
            'options' => array(
                'label' => 'Versie'
            )
        ));

        // TODO: auto completion of organs
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
            'name' => 'changes',
            'type' => 'radio',
            'options' => array(
                'label' => 'Wijzigingen',
                'value_options' => array(
                    'yes' => 'Met wijzigingen',
                    'no' => 'Zonder wijzigingen'
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

        $filter->add(array(
            'name' => 'type',
            'required' => true,
            'validators' => array(
                array(
                    'name' => 'in_array',
                    'options' => array(
                        'haystack' => array(
                            SubDecision::TYPE_BUDGET,
                            SubDecision::TYPE_RECKONING
                        )
                    )
                )
            )
        ));

        $filter->add(array(
            'name' => 'name',
            'required' => true,
            'validators' => array(
                array(
                    'name' => 'string_length',
                    'options' => array(
                        'min' => 3,
                        'max' => 255
                    )
                )
            )
        ));

        $filter->add(array(
            'name' => 'date',
            'required' => true,
            'validators' => array(
                array('name' => 'date')
            )
        ));

        // TODO: update author check

        $filter->add(array(
            'name' => 'version',
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
        ));

        $filter->add(array(
            'name' => 'organ',
            'required' => false,
            'validators' => array(
                array(
                    'name' => 'string_length',
                    'options' => array(
                        'min' => 1,
                        'max' => 64
                    )
                )
            )
        ));

        $filter->add(array(
            'name' => 'approve',
            'required' => true,
            'validators' => array(
                array(
                    'name' => 'in_array',
                    'options' => array(
                        'haystack' => array(
                            'approve',
                            'disapprove'
                        )
                    )
                )
            )
        ));

        $filter->add(array(
            'name' => 'changes',
            'required' => true,
            'validators' => array(
                array(
                    'name' => 'in_array',
                    'options' => array(
                        'haystack' => array(
                            'yes',
                            'no'
                        )
                    )
                )
            )
        ));

        $this->setInputFilter($filter);
    }
}
