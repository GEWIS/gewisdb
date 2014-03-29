<?php

namespace Database\Form\Fieldset;

use Zend\Form\Fieldset;

class MemberFunction extends Fieldset
{

    public function __construct(Member $member)
    {
        parent::__construct('member_function');

        $this->add($member);

        $this->add(array(
            'name' => 'function',
            'type' => 'select',
            'options' => array(
                'label' => 'Functie',
                'value_options' => array(
                    'lid' => 'Lid',
                    'vz' => 'Voorzitter',
                    'secr' => 'Secretaris',
                    'pm' => 'Penningmeester',
                    'vvz' => 'Vice-Voorzitter',
                    'prf' => 'PR-Functionaris',
                    'oc' => 'Onderwijscommisaris'
                )
            )
        ));
    }

    public function getInputFilterSpecification()
    {
        return array();
    }
}
