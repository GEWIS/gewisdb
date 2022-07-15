<?php

namespace Database\Form\Fieldset;

use Database\Service\InstallationFunction as FunctionService;
use Laminas\Form\Fieldset;

class MemberFunction extends Fieldset
{
    public function __construct(Member $member, FunctionService $service, $withmember = false)
    {
        parent::__construct('member_function');

        $this->add($member);

        $this->add([
            'name' => 'function',
            'type' => 'select',
            'options' => [
                'label' => 'Functie',
                'value_options' => $this->getValueOptions($service, $withmember),
            ],
        ]);
    }

    protected function getValueOptions(FunctionService $service, $withmember)
    {
        $array = [];

        if ($withmember) {
            $array['Lid'] = 'Lid';
        }

        foreach ($service->getAllFunctions() as $function) {
            $array[$function->getName()] = $function->getName();
        }

        return $array;
    }

    public function getInputFilterSpecification(): array
    {
        return [];
    }
}
