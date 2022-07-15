<?php

namespace Database\Form\Fieldset;

use Database\Model\SubDecision\Installation as InstallationModel;
use Laminas\Form\Fieldset;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\StringLength;

class Installation extends Fieldset implements InputFilterProviderInterface
{
    public function __construct(Member $member)
    {
        parent::__construct('installation');

        $member->remove('name');
        $this->add($member);

        $this->add([
            'name' => 'function',
            'type' => 'hidden',
        ]);
    }

    public function getInputFilterSpecification(): array
    {
        return [
            'function' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 2,
                        ],
                    ],
                ],
            ],
        ];
    }
}
