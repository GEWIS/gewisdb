<?php

declare(strict_types=1);

namespace Database\Form\Fieldset;

use Database\Model\Enums\InstallationFunctions;
use Laminas\Form\Element\Select;
use Laminas\Form\Fieldset;
use Laminas\Mvc\I18n\Translator;

class MemberFunction extends Fieldset
{
    public function __construct(
        private readonly Translator $translator,
        Member $member,
        bool $withmember = false,
        bool $withLegacy = false,
    ) {
        parent::__construct('member_function');

        $this->add($member);

        $this->add([
            'name' => 'function',
            'type' => Select::class,
            'options' => [
                'label' => 'Functie',
                'value_options' => InstallationFunctions::getFunctionsArray($translator, $withmember, $withLegacy),
            ],
        ]);
    }

    public function getInputFilterSpecification(): array
    {
        // TODO: Check against the known value options (just populate a property while getting the options).
        return [];
    }
}
