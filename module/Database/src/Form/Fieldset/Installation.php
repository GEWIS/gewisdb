<?php

declare(strict_types=1);

namespace Database\Form\Fieldset;

use Database\Form\Fieldset\Member as MemberFieldset;
use Laminas\Form\Element\Hidden;
use Laminas\Form\Fieldset;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\StringLength;

class Installation extends Fieldset implements InputFilterProviderInterface
{
    public function __construct(MemberFieldset $member)
    {
        parent::__construct('installation');

        $member->remove('name');
        $this->add($member);

        $this->add([
            'name' => 'function',
            'type' => Hidden::class,
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
