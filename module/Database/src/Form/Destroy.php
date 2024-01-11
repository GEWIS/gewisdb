<?php

declare(strict_types=1);

namespace Database\Form;

use Database\Form\Fieldset\Decision as DecisionFieldset;
use Database\Form\Fieldset\Meeting as MeetingFieldset;
use Laminas\Form\Element\Submit;
use Laminas\Form\Element\Text;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;

/**
 * @phpstan-import-type AbstractDecisionFormType from AbstractDecision
 * @phpstan-import-type DecisionFieldsetType from DecisionFieldset
 * @phpstan-type DestroyDecisionFormType = array{
 *  name: string,
 *  decision: DecisionFieldsetType,
 * }
 * @extends AbstractDecision<DestroyDecisionFormType & AbstractDecisionFormType>
 */
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
