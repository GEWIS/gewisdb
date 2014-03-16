<?php

namespace Database\Form\Fieldset;

class MemberFunction extends Member
{

    public function __construct()
    {
        parent::__construct();

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
        $spec = parent::getInputFilterSpecification();
        $spec['function'] = array(
            'required' => true,
            'validators' => array(
                array(
                    'name' => 'in_array',
                    'options' => array(
                        'haystack' => array(
                            'lid',
                            'vz',
                            'secr',
                            'pm',
                            'vvz',
                            'prf',
                            'oc'
                        )
                    )
                )
            )
        );
        return $spec;
    }
}
