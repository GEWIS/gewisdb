<?php

declare(strict_types=1);

namespace Database\Form;

use Laminas\Form\Element\Checkbox;
use Laminas\Mvc\I18n\Translator;

/**
 * @template TFilteredValues
 *
 * @extends MemberType<TFilteredValues>
 */
class MemberApprove extends MemberType
{
    public function __construct(private readonly Translator $translator)
    {
        parent::__construct($this->translator);

        $this->add([
            'name' => 'updatedata',
            'type' => Checkbox::class,
            'options' => [
                'label' => $this->translator->translate('Update with data from the TU/e'),
                'use_hidden_element' => false,
            ],
        ]);
    }
}
