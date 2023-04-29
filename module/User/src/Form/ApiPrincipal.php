<?php

declare(strict_types=1);

namespace User\Form;

use Laminas\Form\Element\MultiCheckbox;
use Laminas\Form\Element\Submit;
use Laminas\Form\Element\Text;
use Laminas\Form\Form;
use Laminas\I18n\Translator\TranslatorInterface;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\StringLength;
use User\Model\Enums\ApiPermissions;

class ApiPrincipal extends Form implements InputFilterProviderInterface
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
        parent::__construct();

        $this->add([
            'name' => 'description',
            'type' => Text::class,
            'options' => [
                'label' => 'Description',
            ],
        ]);

        $this->add([
            'type' => MultiCheckbox::class,
            'name' => 'permissions',
            'options' => [
                'label' => 'Select API permissions',
                'value_options' => ApiPermissions::toArray($this->translator),
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'options' => [
                'label' => 'Create API principal',
            ],
        ]);
    }

    /**
     * Specification of input filter.
     *
     * @return array<array-key,mixed>
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'description' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 8,
                            'max' => 255,
                        ],
                    ],
                ],
            ],
        ];
    }
}
