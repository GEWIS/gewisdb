<?php

namespace Database\Form;

use Database\Form\Fieldset\CollectionWithErrors;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterProviderInterface;

class Foundation extends AbstractDecision
    implements InputFilterProviderInterface
{

    public function __construct(Fieldset\Meeting $meeting, Fieldset\MemberFunction $function)
    {
        parent::__construct($meeting);

        $this->add(array(
            'name' => 'type',
            'type' => 'radio',
            'options' => array(
                'label' => 'Type',
                'value_options' => array(
                    'committee' => 'Commissie',
                    'avc' => 'AV-Commissie',
                    'avw' => 'AV-Werkgroep',
                    'kkk' => 'KKK',
                    'fraternity' => 'Dispuut',
                    'rva' => 'RvA'
                )
            )
        ));

        $this->add(array(
            'name' => 'name',
            'type' => 'text',
            'options' => array(
                'label' => 'Naam',
            )
        ));

        $this->add(array(
            'name' => 'abbr',
            'type' => 'text',
            'options' => array(
                'label' => 'Afkorting'
            )
        ));

        // Is this possible with a factory?
        $members = new CollectionWithErrors();
        $members->setName('members');
        $members->setOptions(array(
            'label' => 'Members',
            'count' => 2,
            'should_create_template' => true,
            'target_element' => $function
        ));
        $this->add($members);

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => 'Richt op'
            )
        ));
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification()
    {
        return array(
            'name' => array(
                'required' => true,
                'validators' => array(
                    array(
                        'name' => 'string_length',
                        'options' => array(
                            'min' => 2,
                            'max' => 128
                        )
                    )
                )
            ),

            'abbr' => array(
                'required' => true,
                'validators' => array(
                    array(
                        'name' => 'string_length',
                        'options' => array(
                            'min' => 2,
                            'max' => 32
                        )
                    )
                )
            ),

            'members' => array(
                'continue_if_empty' => true,
                'validators' => [
                    [
                        'name' => 'notEmpty',
                    ]
                ]
            )
        );
    }
}
