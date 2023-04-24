<?php

declare(strict_types=1);

namespace Database\Form;

use Database\Form\Fieldset\{
    Meeting as MeetingFieldset,
    Member as MemberFieldset,
};
use Laminas\Form\Element\{
    Date,
    Radio,
    Select,
    Submit,
    Text,
};
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\{
    Date as DateValidator,
    InArray,
    StringLength,
};

class Budget extends AbstractDecision implements InputFilterProviderInterface
{
    public function __construct(
        private readonly Translator $translator,
        MeetingFieldset $meeting,
        MemberFieldset $member,
    ) {
        parent::__construct($meeting);

        $this->add([
            'name' => 'type',
            'type' => Select::class,
            'options' => [
                'label' => $this->translator->translate('Budget/Statement'),
                'value_options' => [
                    'budget' => $this->translator->translate('Budget'),
                    'reckoning' => $this->translator->translate('Statement'),
                ],
            ],
        ]);

        $this->add([
            'name' => 'name',
            'type' => Text::class,
            'options' => [
                'label' => $this->translator->translate('Name'),
            ],
        ]);

        $this->add([
            'name' => 'date',
            'type' => Date::class,
            'options' => [
                'label' => $this->translator->translate('Date of Budget/Statement'),
            ],
        ]);

        $member->setName('author');
        $member->setLabel($this->translator->translate('Author'));
        $this->add($member);

        $this->add([
            'name' => 'version',
            'type' => Text::class,
            'options' => [
                'label' => $this->translator->translate('Version'),
            ],
        ]);

        $this->add([
            'name' => 'approve',
            'type' => Radio::class,
            'options' => [
                'label' => $this->translator->translate('Approval'),
                'value_options' => [
                    '1' => $this->translator->translate('Approve'),
                    '0' => $this->translator->translate('Disapprove'),
                ],
            ],
        ]);

        $this->add([
            'name' => 'changes',
            'type' => Radio::class,
            'options' => [
                'label' => $this->translator->translate('Modifications'),
                'value_options' => [
                    '1' => $this->translator->translate('With Modifications'),
                    '0' => $this->translator->translate('Without Modifications'),
                ],
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => $this->translator->translate('Add Budget/Statement'),
            ],
        ]);
    }

    public function getInputFilterSpecification(): array
    {
        return [
            'type' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => InArray::class,
                        'options' => [
                            'haystack' => [
                                'budget',
                                'reckoning',
                            ],
                        ],
                    ],
                ],
            ],
            'name' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 3,
                            'max' => 255,
                        ],
                    ],
                ],
            ],
            'date' => [
                'required' => true,
                'validators' => [
                    ['name' => DateValidator::class],
                ],
            ],
            // TODO: update author check
            'version' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 1,
                            'max' => 32,
                        ],
                    ],
                ],
            ],
            // Boolean values have no filter. The form will make sure that it will be casted to true or false
            // And because of the filters the filter is unable to detect if a value is set.
            'approve' => [
                'required' => true,
                'allow_empty' => false,
                'fallback_value' => false,
            ],
            'changes' => [
                'required' => true,
                'allow_empty' => false,
                'fallback_value' => false,
            ],
        ];
    }
}
