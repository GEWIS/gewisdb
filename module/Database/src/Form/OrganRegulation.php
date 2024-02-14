<?php

declare(strict_types=1);

namespace Database\Form;

use Database\Form\Fieldset\Meeting as MeetingFieldset;
use Database\Form\Fieldset\Member as MemberFieldset;
use Laminas\Form\Element\Date;
use Laminas\Form\Element\Radio;
use Laminas\Form\Element\Submit;
use Laminas\Form\Element\Text;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\Callback;
use Laminas\Validator\Date as DateValidator;
use Laminas\Validator\StringLength;

class OrganRegulation extends AbstractDecision implements InputFilterProviderInterface
{
    public function __construct(
        private readonly Translator $translator,
        MeetingFieldset $meeting,
        MemberFieldset $member,
    ) {
        parent::__construct($meeting);

        $this->add([
            'name' => 'type',
            'type' => Radio::class,
            'options' => [
                'label' => 'Type',
                'value_options' => [
                    [
                        'value' => 'committee',
                        'label' => $this->translator->translate('Committee'),
                    ],
                    [
                        'value' => 'fraternity',
                        'label' => $this->translator->translate('Fraternity'),
                    ],
                    [
                        'value' => 'avc',
                        'label' => $this->translator->translate('GMM Committee'),
                        'disabled' => true,
                    ],
                    [
                        'value' => 'avw',
                        'label' => $this->translator->translate('GMM Taskforce'),
                        'disabled' => true,
                    ],
                    [
                        'value' => 'kcc',
                        'label' => $this->translator->translate('Financial Audit Committee'),
                        'disabled' => true,
                    ],
                    [
                        'value' => 'rva',
                        'label' => $this->translator->translate('Advisory Board'),
                        'disabled' => true,
                    ],
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
                'label' => $this->translator->translate('Date of Organ Regulation'),
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
                'value' => $this->translator->translate('Add Organ Regulation'),
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
                        'name' => Callback::class,
                        'options' => [
                            'messages' => [
                                Callback::INVALID_VALUE => $this->translator->translate(
                                    'Organ regulations can only be created for \'committee\' or \'fraternity\'.',
                                ),
                            ],
                            'callback' => [$this, 'organTypeNotDisabled'],
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
                            'min' => 2,
                            'max' => 128,
                        ],
                    ],
                ],
            ],
            'date' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => DateValidator::class,
                    ],
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
            // Boolean values have no filter. The form will make sure that it will be cast to true or false
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

    public function organTypeNotDisabled(string $value): bool
    {
        /** @var Radio $element */
        $element = $this->get('type');

        foreach ($element->getValueOptions() as $option) {
            if (
                $option['value'] === $value
                && (
                    !isset($option['disabled'])
                    || true !== $option['disabled']
                )
            ) {
                return true;
            }
        }

        return false;
    }
}
