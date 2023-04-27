<?php

declare(strict_types=1);

namespace Database\Form\Fieldset;

use Laminas\Form\Element\Hidden;
use Laminas\Form\Element\Text;
use Laminas\Form\Fieldset;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\Digits;

class Member extends Fieldset implements InputFilterProviderInterface
{
    public function __construct()
    {
        parent::__construct('member');

        // is only there for fun, will only be used as source for 'lidnr' autocomplete
        // which uses AJAX to find members
        $this->add([
            'name' => 'name',
            'type' => Text::class,
            'options' => ['label' => 'Lid'],
        ]);

        // actual way to find the member
        $this->add([
            'name' => 'lidnr',
            'type' => Hidden::class,
        ]);
    }

    public function getInputFilterSpecification(): array
    {
        return [
            'lidnr' => [
                'required' => true,
                'validators' => [
                    ['name' => Digits::class],
                ],
            ],
        ];
    }
}
