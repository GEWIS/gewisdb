<?php

declare(strict_types=1);

namespace Database\Form\Fieldset;

use Database\Model\Enums\Studies;
use Laminas\Form\Element\Select;
use Laminas\Form\Fieldset;
use Laminas\Mvc\I18n\Translator;

class Study extends Fieldset
{
    public function __construct(
        private readonly Translator $translator,
    ) {
        parent::__construct('study');

        $this->add([
            'name' => 'study',
            'type' => Select::class,
            'options' => [
                'label' => $translator->translate('Study'),
                'value_options' => Studies::getFunctionsArray($translator, true),
                'empty_option' => $translator->translate('Select a study'),
            ],
            'attributes' => [
                'value' => '',
            ],
        ]);
    }

    /**
     * Specification of input filter.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'study' => [
                'required' => true,
            ],
        ];
    }
}
