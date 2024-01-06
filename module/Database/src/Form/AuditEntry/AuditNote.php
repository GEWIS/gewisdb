<?php

declare(strict_types=1);

namespace Database\Form\AuditEntry;

use Laminas\Form\Element\Submit;
use Laminas\Form\Element\Text;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Laminas\Validator\StringLength;

/**
 * @template TFilteredValues
 *
 * @extends Form<TFilteredValues>
 */
class AuditNote extends Form implements InputFilterProviderInterface
{
    public function __construct(private readonly Translator $translator)
    {
        parent::__construct();

        $this->add([
            'name' => 'note',
            'type' => Text::class,
            'options' => [
                'label' => $this->translator->translate('Note'),
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
        ]);
        $this->get('submit')->setLabel($translator->translate('Leave note'));
    }

    /**
     * Specification of input filter.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'note' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 2,
                            'max' => 255,
                        ],
                    ],
                ],
            ],
        ];
    }
}
