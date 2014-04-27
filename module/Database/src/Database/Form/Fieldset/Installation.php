<?php

namespace Database\Form\Fieldset;

use Database\Model\SubDecision\Installation as InstallationModel;

class Installation extends Member
{

    public function __construct()
    {
        $this->setName('installation');
        $this->remove('name');

        $this->add(array(
            'name' => 'function',
            'type' => 'hidden'
        ));
    }

    public function getInputFilterSpecification()
    {
        $spec = parent::getInputFilterSpecification();
        $spec['function'] = array(
            'required' => rue,
            'validators' => array(
                array(
                    'name' => 'in_array',
                    'options' => array(
                        'haystack' => InstallationModel::getFunctions()
                    )
                )
            )
        );
        return $spec;
    }
}
