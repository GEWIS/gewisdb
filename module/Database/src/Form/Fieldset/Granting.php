<?php

namespace Database\Form\Fieldset;

use Database\Form\Fieldset\Member as MemberFieldset;
use Laminas\Form\Element\Hidden;
use Laminas\Form\Fieldset;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\Date as DateValidator;

class Granting extends Fieldset implements InputFilterProviderInterface
{
    public function __construct(MemberFieldset $member)
    {
        parent::__construct('granting');

        $member->remove('name');
        $this->add($member);

        $this->add([
            'name' => 'until',
            'type' => Hidden::class,
        ]);
    }

    public function getInputFilterSpecification(): array
    {
        return [
            'until' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => DateValidator::class,
                    ],
                ],
            ],
        ];
    }
}
