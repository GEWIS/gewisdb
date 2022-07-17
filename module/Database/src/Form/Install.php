<?php

namespace Database\Form;

use Database\Form\Fieldset\{
    Installation as InstallationFieldset,
    Meeting as MeetingFieldset,
    SubDecision as SubDecisionFieldset,
};
use Laminas\Form\Element\{
    Collection,
    Submit,
    Text,
};

class Install extends AbstractDecision
{
    public function __construct(
        MeetingFieldset $meeting,
        InstallationFieldset $install,
        SubDecisionFieldset $discharge,
        SubDecisionFieldset $foundation,
    ) {
        parent::__construct($meeting);

        $this->add([
            'name' => 'name',
            'type' => Text::class,
            'options' => [
                'label' => 'Orgaan',
            ],
        ]);

        $this->add($foundation);

        $this->add([
            'name' => 'installations',
            'type' => Collection::class,
            'options' => [
                'label' => 'Installations',
                'count' => 1,
                'should_create_template' => true,
                'target_element' => $install,
            ],
        ]);

        $this->add([
            'name' => 'discharges',
            'type' => Collection::class,
            'options' => [
                'label' => 'Members',
                'count' => 1,
                'should_create_template' => true,
                'target_element' => $discharge,
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => 'Maak wijzigingen',
            ],
        ]);
    }
}
