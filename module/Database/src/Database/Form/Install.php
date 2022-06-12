<?php

namespace Database\Form;

use Zend\InputFilter\InputFilter;

class Install extends AbstractDecision
{
    public function __construct(
        Fieldset\Meeting $meeting,
        Fieldset\Installation $install,
        Fieldset\SubDecision $discharge,
        Fieldset\SubDecision $foundation
    ) {
        parent::__construct($meeting);

        $this->add([
            'name' => 'name',
            'type' => 'text',
            'options' => [
                'label' => 'Orgaan',
            ]
        ]);

        $this->add($foundation);

        $this->add([
            'name' => 'installations',
            'type' => 'collection',
            'options' => [
                'label' => 'Installations',
                'count' => 1,
                'should_create_template' => true,
                'target_element' => $install
            ]
        ]);

        $this->add([
            'name' => 'discharges',
            'type' => 'collection',
            'options' => [
                'label' => 'Members',
                'count' => 1,
                'should_create_template' => true,
                'target_element' => $discharge
            ]
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Maak wijzigingen'
            ]
        ]);
    }
}
