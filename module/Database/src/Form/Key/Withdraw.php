<?php

declare(strict_types=1);

namespace Database\Form\Key;

use Database\Form\AbstractDecision;
use Database\Form\Fieldset\Granting as GrantingFieldset;
use Database\Form\Fieldset\Meeting as MeetingFieldset;
use Database\Form\Fieldset\SubDecision as SubDecisionFieldset;
use DateTime;
use Laminas\Form\Element\Date;
use Laminas\Form\Element\Submit;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\Callback;
use Throwable;

class Withdraw extends AbstractDecision implements InputFilterProviderInterface
{
    public function __construct(
        private readonly Translator $translator,
        MeetingFieldset $meeting,
        SubDecisionFieldset $subdecision,
        GrantingFieldset $granting,
    ) {
        parent::__construct($meeting);

        $this->add(clone $subdecision);
        $this->add(clone $granting);

        $this->add([
            'name' => 'withdrawOn',
            'type' => Date::class,
            'options' => [
                'label' => $this->translator->translate('Effective From'),
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => $this->translator->translate('Withdraw Key Code'),
            ],
        ]);
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'withdrawOn' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => Callback::class,
                        'options' => [
                            'callback' => function ($value, $context) {
                                return $this->isNotInThePast($value, $context);
                            },
                            'messages' => [
                                Callback::INVALID_VALUE => $this->translator->translate(
                                    'Key code cannot be withdrawn in the past.',
                                ),
                            ],
                        ],
                    ],
                    [
                        'name' => Callback::class,
                        'options' => [
                            'callback' => function ($value, $context = []) {
                                return $this->isNotAfterGranting($value, $context);
                            },
                            'messages' => [
                                Callback::INVALID_VALUE => $this->translator->translate(
                                    'Key code cannot be withdrawn after its original expiration.',
                                ),
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    private function isNotInThePast(
        string $value,
        array $context = [],
    ): bool {
        try {
            $today = new DateTime($context['meeting']['date']);

            return (new DateTime($value)) >= $today;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    private function isNotAfterGranting(
        string $value,
        array $context = [],
    ): bool {
        try {
            $until = new DateTime($context['granting']['until']);

            return (new DateTime($value)) <= $until;
        } catch (Throwable) {
            return false;
        }
    }
}
