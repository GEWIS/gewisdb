<?php

declare(strict_types=1);

namespace Database\Form;

use Laminas\Form\Element\Checkbox;
use Laminas\Form\Element\Submit;
use Laminas\Form\Element\Text;
use Laminas\Form\Element\Textarea;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\StringLength;

class MailingList extends Form implements InputFilterProviderInterface
{
    public function __construct(private readonly Translator $translator)
    {
        parent::__construct();

        $this->add([
            'name' => 'name',
            'type' => Text::class,
            'options' => [
                'label' => $this->translator->translate('Name'),
            ],
        ]);

        $this->add([
            'name' => 'nl_description',
            'type' => Textarea::class,
            'options' => [
                'label' => $this->translator->translate('Dutch Description'),
            ],
        ]);

        $this->add([
            'name' => 'en_description',
            'type' => Textarea::class,
            'options' => [
                'label' => $this->translator->translate('English Description'),
            ],
        ]);

        $this->add([
            'name' => 'onForm',
            'type' => Checkbox::class,
            'options' => [
                'label' => $this->translator->translate('On Form'),
            ],
        ]);
        $this->get('onForm')->setChecked(true);

        $this->add([
            'name' => 'defaultSub',
            'type' => Checkbox::class,
            'options' => [
                'label' => $this->translator->translate('Auto-subscription'),
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => $this->translator->translate('Add Mailing List'),
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
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 2,
                            'max' => 64,
                        ],
                    ],
                ],
            ],
            'nl_description' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 10,
                        ],
                    ],
                ],
            ],
            'en_description' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 10,
                        ],
                    ],
                ],
            ],
        ];
    }
}
