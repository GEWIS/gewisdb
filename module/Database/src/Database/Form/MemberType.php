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
                    Member::TYPE_ORDINARY  => 'Gewoon - Ingeschreven als student bij faculteit M&CS',
                    Member::TYPE_EXTERNAL  => 'Extern -  Op enigerlei wijze belangstelling vertonen voor de doelstelling van de vereniging',
                    Member::TYPE_HONORARY  => 'Erelid - Wegens bijzondere verdiensten als zodanig zijn benoemd door de ALV',
                    Member::TYPE_DONATOR   => 'Donateur - Steunt vereniging met een minimum bijdrage',
                    Member::TYPE_GRADUATED => 'Afgestudeerd - Oud-leden van de vereniging die op hun verzoek zijn toegelaten',
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
        );
    }
}
