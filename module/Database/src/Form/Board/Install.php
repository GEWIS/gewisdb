<?php

namespace Database\Form\Board;

use Database\Form\AbstractDecision;
use Laminas\Mvc\I18n\Translator;
use Database\Form\Fieldset\{
    Meeting as MeetingFieldset,
    Member as MemberFieldset,
};
use Laminas\Form\Element\{
    Date,
    Submit,
    Text,
};
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\StringLength;

class Install extends AbstractDecision implements InputFilterProviderInterface
{
    public function __construct(
        private readonly Translator $translator,
        MeetingFieldset $meeting,
        MemberFieldset $member,
    ) {
        parent::__construct($meeting);

        $this->add(clone $member);

        $this->add([
            'name' => 'function',
            'type' => Text::class,
            'options' => [
                'label' => $this->translator->translate('Function'),
            ],
        ]);

        $this->add([
            'name' => 'date',
            'type' => Date::class,
            'options' => [
                'label' => $this->translator->translate('Effective From'),
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => $this->translator->translate('Install Board Member'),
            ],
        ]);
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'function' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 2,
                            'max' => 32,
                        ],
                    ],
                ],
            ],
            'date' => [
                'required' => true,
            ],
        ];
    }
}
