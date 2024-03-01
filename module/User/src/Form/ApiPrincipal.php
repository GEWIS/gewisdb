<?php

declare(strict_types=1);

namespace User\Form;

use Laminas\Form\Element\MultiCheckbox;
use Laminas\Form\Element\Submit;
use Laminas\Form\Element\Text;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\StringLength;
use User\Model\ApiPrincipal as ApiPrincipalmodel;
use User\Model\Enums\ApiPermissions;

use function array_map;
use function in_array;

/**
 * @template TFilteredValues
 *
 * @extends Form<TFilteredValues>
 */
class ApiPrincipal extends Form implements InputFilterProviderInterface
{
    public function __construct(
        private readonly Translator $translator,
    ) {
        parent::__construct();

        $this->add([
            'name' => 'description',
            'type' => Text::class,
            'options' => [
                'label' => $this->translator->translate('Description'),
            ],
        ]);

        $this->add([
            'name' => 'token',
            'type' => Text::class,
            'options' => [
                'label' => $this->translator->translate('Token'),
            ],
            'attributes' => [
                'readonly' => true,
            ],
        ]);

        $permissions = [];
        foreach (ApiPermissions::toArray($this->translator) as $value => $label) {
            $permissions[] = [
                'value' => $value,
                'label' => $label,
            ];
        }

        $this->add([
            'type' => MultiCheckbox::class,
            'name' => 'permissions',
            'options' => [
                'label' => $this->translator->translate('Select API permissions'),
                'value_options' => $permissions,
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'options' => [
                'label' => $this->translator->translate('Create API principal'),
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

    public function bind(
        object $object,
        int $flags = Form::VALUES_NORMALIZED,
    ): void {
        if (!($object instanceof ApiPrincipalModel)) {
            return;
        }

        parent::bind($object, $flags);

        $permissions = $object->getPermissions();
        $this->get('permissions')->setValueOptions(
            array_map(
                static function ($options) use ($permissions) {
                    $options['selected'] = in_array(ApiPermissions::tryFrom($options['value']), $permissions);

                    return $options;
                },
                $this->get('permissions')->getValueOptions(),
            ),
        );
    }
}
