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
                    'member' => 'Lid',
                    'chairman' => 'Voorzitter',
                    'secretary' => 'Secretaris',
                    'treasurer' => 'Penningmeester',
                    'vice-chairman' => 'Vice-Voorzitter',
                    'pr-officer' => 'PR-Functionaris',
                    'education-officer' => 'Onderwijscommisaris'
                )
            )
        ));
    }

    public function getInputFilterSpecification()
    {
        return array();
    }
}
