<?php

namespace Database\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterProviderInterface;
use Database\Model\Member;

class MemberType extends Form implements InputFilterProviderInterface
{
    public function __construct()
    {
        parent::__construct();

        $this->add(array(
            'name' => 'type',
            'type' => 'radio',
            'options' => array(
                'label' => 'Lidmaatschapstype',
                'value_options' => array(
                    Member::TYPE_ORDINARY => 'Gewoon lid - Ingeschreven bij faculteit M&CS',
                    Member::TYPE_EXTERNAL => 'Extern lid - Speciaal toegelaten door het bestuur',
                    Member::TYPE_GRADUATE => 'Afgestudeerde - Was lid en is speciaal toegelaten door het bestuur',
                    Member::TYPE_HONORARY => 'Erelid - Speciaal benoemd door de ALV'
                )
            )
        ));

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => 'Wijzig type'
            )
        ));
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification()
    {
        return array(
            'type' => array(
                'required' => true
            )
        );
    }
}
