<?php

declare(strict_types=1);

namespace Database\Form\Fieldset;

use Database\Service\InstallationFunction as FunctionService;
use Laminas\Form\Element\Select;
use Laminas\Form\Fieldset;

class MemberFunction extends Fieldset
{
    public function __construct(
        Member $member,
        FunctionService $service,
        bool $withmember = false,
    ) {
        parent::__construct('member_function');

        $this->add($member);

        $this->add([
            'name' => 'function',
            'type' => Select::class,
            'options' => [
                'label' => 'Functie',
                'value_options' => $this->getValueOptions($service, $withmember),
            ],
        ]);
    }

    protected function getValueOptions(
        FunctionService $service,
        bool $withmember,
    ): array {
        $array = [];

        if ($withmember) {
            $array['Lid'] = 'Lid';
            $array['Inactief Lid'] = 'Inactief Lid';
        }

        foreach ($service->getAllFunctions() as $function) {
            $array[$function->getName()] = $function->getName();
        }

        return $array;
    }

    public function getInputFilterSpecification(): array
    {
        // TODO: Check against the known value options (just populate a property while getting the options).
        return [];
    }
}
