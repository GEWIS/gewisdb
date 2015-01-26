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
                    Member::TYPE_ORDINARY => 'Gewoon - Ingeschreven bij faculteit W&I',
                    Member::TYPE_PROLONGED => 'Geprolongeerd - Verlengd ingeschreven bij faculteit W&I',
                    Member::TYPE_EXTERNAL => 'Extern - Was gewoon lid, maar is niet meer ingeschreven bij W&I',
                    Member::TYPE_EXTRAORDINARY => 'Buitengewoon - Speciaal toegelaten door bestuur',
                    Member::TYPE_HONORARY => 'Erelid'
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
