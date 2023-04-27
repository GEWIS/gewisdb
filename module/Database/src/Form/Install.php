<?php

declare(strict_types=1);

namespace Database\Form;

use Database\Form\Fieldset\Installation as InstallationFieldset;
use Database\Form\Fieldset\Meeting as MeetingFieldset;
use Database\Form\Fieldset\SubDecision as SubDecisionFieldset;
use Laminas\Form\Element\Collection;
use Laminas\Form\Element\Submit;
use Laminas\Form\Element\Text;
use Laminas\Mvc\I18n\Translator;

class Install extends AbstractDecision
{
    public function __construct(
        private readonly Translator $translator,
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
                'label' => $this->translator->translate('Organ'),
            ],
        ]);

        $this->add($foundation);

        $this->add([
            'name' => 'installations',
            'type' => Collection::class,
            'options' => [
                'label' => $this->translator->translate('Installations'),
                'count' => 1,
                'should_create_template' => true,
                'target_element' => $install,
            ],
        ]);

        $this->add([
            'name' => 'discharges',
            'type' => Collection::class,
            'options' => [
                'label' => $this->translator->translate('Discharges'),
                'count' => 1,
                'should_create_template' => true,
                'target_element' => $discharge,
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => $this->translator->translate('Confirm Changes'),
            ],
        ]);
    }
}
