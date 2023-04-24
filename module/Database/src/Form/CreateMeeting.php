<?php

declare(strict_types=1);

namespace Database\Form;

use Application\Model\Enums\MeetingTypes;
use Laminas\Form\Element\{
    Date,
    Select,
    Submit,
    Text,
};
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\{
    Date as DateValidator,
    Digits,
    InArray,
    LessThan,
};

class CreateMeeting extends Form implements InputFilterProviderInterface
{
    public function __construct(private readonly Translator $translator)
    {
        parent::__construct();

        $this->add([
            'name' => 'type',
            'type' => Select::class,
            'options' => [
                'label' => $this->translator->translate('Meeting Type'),
                'value_options' => [
                    'BV' => $this->translator->translate('BM (Board Meeting)'),
                    'ALV' => $this->translator->translate('GMM (General Members Meeting)'),
                    'VV' => $this->translator->translate('CM (Chair\'s Meeting)'),
                    'Virt' => $this->translator->translate('Virt (Virtual Meeting)'),
                ],
            ],
        ]);

        $this->add([
            'name' => 'number',
            'type' => Text::class,
            'options' => [
                'label' => $this->translator->translate('Meeting Number'),
            ],
        ]);

        $this->add([
            'name' => 'date',
            'type' => Date::class,
            'options' => [
                'label' => $this->translator->translate('Meeting Date'),
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => $this->translator->translate('Add Meeting'),
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
                            'haystack' => MeetingTypes::values(),
                        ],
                    ],
                ],
            ],
            'number' => [
                'required' => true,
                'validators' => [
                    ['name' => Digits::class],
                    [
                        'name' => LessThan::class,
                        'options' => [
                            'max' => 100000,
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
        ];
    }
}
