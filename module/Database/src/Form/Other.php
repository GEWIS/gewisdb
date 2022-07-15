<?php

namespace Database\Form;

use Database\Model\SubDecision;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\StringLength;

class Other extends AbstractDecision implements InputFilterProviderInterface
{
    public function __construct(Fieldset\Meeting $meeting)
    {
        parent::__construct($meeting);

        $this->add([
            'name' => 'content',
            'type' => 'text',
            'options' => [
                'label' => 'Besluit',
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Verzend',
            ],
        ]);
    }

    public function getInputFilterSpecification(): array
    {
        return [
            'content' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 3,
                        ],
                    ],
                ],
            ],
        ];
    }
}
