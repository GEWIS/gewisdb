<?php

declare(strict_types=1);

namespace Database\Form;

use Database\Form\Fieldset\Meeting as MeetingFieldset;
use Laminas\Form\Element\Submit;
use Laminas\Form\Element\Text;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\StringLength;

class Other extends AbstractDecision implements InputFilterProviderInterface
{
    public function __construct(
        private readonly Translator $translator,
        MeetingFieldset $meeting,
    ) {
        parent::__construct($meeting);

        $this->add([
            'name' => 'content',
            'type' => Text::class,
            'options' => [
                'label' => $this->translator->translate('Decision'),
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => $this->translator->translate('Add Decision'),
            ],
        ]);
    }

    public function getInputFilterSpecification(): array
    {
        return [
            'content' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 3,
                        ],
                    ],
                ],
            ],
        ];
    }
}
