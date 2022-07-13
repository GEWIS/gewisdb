<?php

namespace Database\Form;

class Install extends AbstractDecision
{
    public function __construct(
        Fieldset\Meeting $meeting,
        Fieldset\Installation $install,
        Fieldset\SubDecision $discharge,
        Fieldset\SubDecision $foundation
    ) {
        parent::__construct($meeting);

        $this->add(array(
            'name' => 'name',
            'type' => 'text',
            'options' => array(
                'label' => 'Orgaan',
            )
        ));

        $this->add($foundation);

        $this->add(array(
            'name' => 'installations',
            'type' => 'collection',
            'options' => array(
                'label' => 'Installations',
                'count' => 1,
                'should_create_template' => true,
                'target_element' => $install
            )
        ));

        $this->add(array(
            'name' => 'discharges',
            'type' => 'collection',
            'options' => array(
                'label' => 'Members',
                'count' => 1,
                'should_create_template' => true,
                'target_element' => $discharge
            )
        ));

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => 'Maak wijzigingen'
            )
        ));
    }
}
