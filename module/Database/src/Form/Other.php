<?php

namespace Database\Form;

use Database\Model\SubDecision;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\Validator\StringLength;

class Other extends AbstractDecision implements InputFilterProviderInterface
{
    public function __construct(Fieldset\Meeting $meeting)
    {
        parent::__construct($meeting);

        $this->add(array(
            'name' => 'content',
            'type' => 'text',
            'options' => array(
                'label' => 'Besluit',
            )
        ));

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => 'Verzend'
            )
        ));
    }

    public function getInputFilterSpecification(): array
    {
        return [
            'content' => array(
                'required' => true,
                'validators' => array(
                    array(
                        'name' => StringLength::class,
                        'options' => array(
                            'min' => 3
                        )
                    )
                )
            ),
        ];
    }
}
