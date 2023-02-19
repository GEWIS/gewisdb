<?php

namespace Database\Form;

use Database\Form\Fieldset\{
    Meeting as MeetingFieldset,
    Decision as DecisionFieldset,
};
use Laminas\Form\Element\{
    Submit,
    Text,
};
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;

class Destroy extends AbstractDecision implements InputFilterProviderInterface
{
    public function __construct(
        private readonly Translator $translator,
        MeetingFieldset $meeting,
        DecisionFieldset $decision,
    ) {
        parent::__construct($meeting);

        $this->add([
            'name' => 'name',
            'type' => Text::class,
            'options' => [
                'label' => $this->translator->translate('Decision'),
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => $this->translator->translate('Annul Decision'),
            ],
        ]);

        $this->add($decision);
    }

    public function getInputFilterSpecification(): array
    {
        return [];
    }
}
