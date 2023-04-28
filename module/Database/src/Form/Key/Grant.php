<?php

declare(strict_types=1);

namespace Database\Form\Key;

use Database\Form\AbstractDecision;
use Database\Form\Fieldset\Meeting as MeetingFieldset;
use Database\Form\Fieldset\Member as MemberFieldset;
use DateInterval;
use DateTime;
use Laminas\Form\Element\Date;
use Laminas\Form\Element\Submit;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\Callback;
use Laminas\Validator\Date as DateValidator;
use Throwable;

class Grant extends AbstractDecision implements InputFilterProviderInterface
{
    public function __construct(
        private readonly Translator $translator,
        MeetingFieldset $meeting,
        MemberFieldset $member,
    ) {
        parent::__construct($meeting);

        $member->setName('grantee');
        $member->setLabel($this->translator->translate('Grantee'));
        $this->add($member);

        $this->add([
            'name' => 'until',
            'type' => Date::class,
            'options' => [
                'label' => $this->translator->translate('Date of Expiration'),
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => $this->translator->translate('Grant Key Code'),
            ],
        ]);
    }

    public function getInputFilterSpecification(): array
    {
        return [
            'until' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => DateValidator::class,
                    ],
                    [
                        'name' => Callback::class,
                        'options' => [
                            'callback' => function ($value) {
                                return $this->isNotInThePast($value);
                            },
                            'messages' => [
                                Callback::INVALID_VALUE => $this->translator->translate(
                                    'Key code cannot be granted in the past.',
                                ),
                            ],
                        ],
                    ],
                    [
                        'name' => Callback::class,
                        'options' => [
                            'callback' => function ($value) {
                                return $this->isMaxOneYear($value);
                            },
                            'messages' => [
                                Callback::INVALID_VALUE => $this->translator->translate(
                                    'Key code cannot be granted for more than one year.',
                                ),
                            ],
                        ],
                    ],
                    [
                        'name' => Callback::class,
                        'options' => [
                            'callback' => function ($value) {
                                return $this->isNotTooFar($value);
                            },
                            'messages' => [
                                Callback::INVALID_VALUE => $this->translator->translate(
                                    'Key code cannot be granted after September 1st of the next association year.',
                                ),
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function isNotInThePast(string $value): bool
    {
        try {
            $today = new DateTime('today');

            return (new DateTime($value)) >= $today;
        } catch (Throwable) {
            return false;
        }
    }

    private function isMaxOneYear(string $value): bool
    {
        try {
            $future = (new DateTime('today'))->add(new DateInterval('P1Y'));

            return (new DateTime($value)) <= $future;
        } catch (Throwable) {
            return false;
        }
    }

    private function isNotTooFar(string $value): bool
    {
        try {
            $today = new DateTime('today');

            if ($today->format('m') >= 7) {
                $year = (int) $today->format('Y') + 1;
            } else {
                $year = (int) $today->format('Y');
            }

            $septemberFirstNextAssociationYear = clone $today;
            $septemberFirstNextAssociationYear->setDate($year, 9, 1);

            return (new DateTime($value)) <= $septemberFirstNextAssociationYear;
        } catch (Throwable) {
            return false;
        }
    }
}
