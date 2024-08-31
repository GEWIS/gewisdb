<?php

declare(strict_types=1);

namespace Database\Form;

use Database\Form\Fieldset\CollectionWithErrors;
use Database\Form\Fieldset\Meeting as MeetingFieldset;
use Database\Form\Fieldset\MemberFunction as MemberFunctionFieldset;
use Laminas\Filter\StringTrim;
use Laminas\Form\Element\Radio;
use Laminas\Form\Element\Submit;
use Laminas\Form\Element\Text;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\NotEmpty;
use Laminas\Validator\StringLength;

class Foundation extends AbstractDecision implements InputFilterProviderInterface
{
    public function __construct(
        private readonly Translator $translator,
        MeetingFieldset $meeting,
        MemberFunctionFieldset $function,
    ) {
        parent::__construct($meeting);

        $this->add([
            'name' => 'type',
            'type' => Radio::class,
            'options' => [
                'label' => 'Type',
                'value_options' => [
                    'committee' => $this->translator->translate('Committee'),
                    'fraternity' => $this->translator->translate('Fraternity'),
                    'avc' => $this->translator->translate('GMM Committee'),
                    'avw' => $this->translator->translate('GMM Taskforce'),
                    'kcc' => $this->translator->translate('Financial Audit Committee'),
                    'rva' => $this->translator->translate('Advisory Board'),
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
            'name' => 'abbr',
            'type' => Text::class,
            'options' => [
                'label' => $this->translator->translate('Abbreviation'),
            ],
        ]);

        // Is this possible with a factory?
        $this->add([
            'name' => 'members',
            'type' => CollectionWithErrors::class,
            'options' => [
                'label' => $this->translator->translate('Members'),
                'count' => 2,
                'allow_add' => true,
                'should_create_template' => true,
                'target_element' => $function,
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => $this->translator->translate('Found Organ'),
            ],
        ]);
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'name' => [
                'required' => true,
                'filters' => [
                    [
                        'name' => StringTrim::class,
                    ],
                ],
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

            'abbr' => [
                'required' => true,
                'filters' => [
                    [
                        'name' => StringTrim::class,
                    ],
                ],
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

            'members' => [
                'continue_if_empty' => true,
                'validators' => [
                    [
                        'name' => NotEmpty::class,
                    ],
                ],
            ],
        ];
    }
}
