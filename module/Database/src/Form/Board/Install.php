<?php

declare(strict_types=1);

namespace Database\Form\Board;

use Database\Form\AbstractDecision;
use Database\Form\Fieldset\Meeting as MeetingFieldset;
use Database\Form\Fieldset\Member as MemberFieldset;
use Laminas\Form\Element\Date;
use Laminas\Form\Element\Submit;
use Laminas\Form\Element\Text;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\StringLength;

/**
 * @psalm-import-type AbstractDecisionFormType from AbstractDecision
 * @psalm-import-type MemberFieldsetType from MemberFieldset
 * @psalm-type InstallDecisionFormType = array{
 *  member: MemberFieldsetType,
 *  function: string,
 *  date: string,
 * }
 * @extends AbstractDecision<AbstractDecisionFormType & InstallDecisionFormType>
 */
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
            'date' => ['required' => true],
        ];
    }
}
