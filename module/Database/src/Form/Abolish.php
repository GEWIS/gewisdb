<?php

declare(strict_types=1);

namespace Database\Form;

use Database\Form\Fieldset\Meeting as MeetingFieldset;
use Database\Form\Fieldset\SubDecision as SubDecisionFieldset;
use Laminas\Form\Element\Submit;
use Laminas\Form\Element\Text;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;

/**
 * @psalm-import-type AbstractDecisionFormType from AbstractDecision
 * @psalm-import-type SubDecisionFieldsetType from SubDecisionFieldset
 * @psalm-type AbolishDecisionFormType = array{
 *  name: string,
 *  subdecision: SubDecisionFieldsetType
 * }
 * @extends AbstractDecision<AbstractDecisionFormType & AbolishDecisionFormType>
 */
class Abolish extends AbstractDecision implements InputFilterProviderInterface
{
    public function __construct(
        private readonly Translator $translator,
        MeetingFieldset $meeting,
        SubDecisionFieldset $subdecision,
    ) {
        parent::__construct($meeting);

        $this->add([
            'name' => 'name',
            'type' => Text::class,
            'options' => [
                'label' => $this->translator->translate('Organ'),
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => $this->translator->translate('Abolish Organ'),
            ],
        ]);

        $this->add($subdecision);
    }

    public function getInputFilterSpecification(): array
    {
        return [];
    }
}
