<?php

namespace Database\Form;

use Database\Model\SubDecision;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Validator\Date;
use Laminas\Validator\InArray;
use Laminas\Validator\StringLength;

class Budget extends AbstractDecision implements InputFilterProviderInterface
{
    public function __construct(Fieldset\Meeting $meeting, Fieldset\Member $member)
    {
        parent::__construct($meeting);

        $this->add(array(
            'name' => 'type',
            'type' => 'select',
            'options' => array(
                'label' => 'Begroting / Afrekening',
                'value_options' => array(
                    'budget' => 'Begroting',
                    'reckoning' => 'Afrekening'
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
            'name' => 'date',
            'type' => 'date',
            'options' => array(
                'label' => 'Datum begroting / afrekening'
            )
        ));

        $member->setName('author');
        $member->setLabel('Auteur');
        $this->add($member);

        $this->add(array(
            'name' => 'version',
            'type' => 'text',
            'options' => array(
                'label' => 'Versie'
            )
        ));

        $this->add(array(
            'name' => 'approve',
            'type' => 'radio',
            'options' => array(
                'label' => 'Goedkeuren / Afkeuren',
                'value_options' => array(
                    'true' => 'Goedkeuren',
                    'false' => 'Afkeuren'
                ),
                // forward compatability with ZF 2.3, doesn't actually do anything right now
                'disable_inarray_validator' => true
            )
        ));

        $this->add(array(
            'name' => 'changes',
            'type' => 'radio',
            'options' => array(
                'label' => 'Wijzigingen',
                'value_options' => array(
                    'true' => 'Met wijzigingen',
                    'false' => 'Zonder wijzigingen'
                ),
                // forward compatability with ZF 2.3, doesn't actually do anything right now
                'disable_inarray_validator' => true
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
            'type' => array(
                'required' => true,
                'validators' => array(
                    array(
                        'name' => InArray::class,
                        'options' => array(
                            'haystack' => array(
                                'budget',
                                'reckoning'
                            )
                        )
                    )
                )
            ),
            'name' => array(
                'required' => true,
                'validators' => array(
                    array(
                        'name' => StringLength::class,
                        'options' => array(
                            'min' => 3,
                            'max' => 255
                        )
                    )
                )
            ),
            'date' => array(
                'required' => true,
                'validators' => array(
                    array('name' => Date::class)
                )
            ),
            // TODO: update author check
            'version' => array(
                'required' => true,
                'validators' => array(
                    array(
                        'name' => StringLength::class,
                        'options' => array(
                            'min' => 1,
                            'max' => 32
                        )
                    )
                )
            ),
            // Boolean values have no filter. The form will make sure that it will be casted to true or false
            // And because of the filters the filter is unable to detect if a value is set.
            'approve' => array(
                'required' => true,
                'allow_empty' => false,
                'fallback_value' => false,
            ),
            'changes' => array(
                'required' => true,
                'allow_empty' => false,
                'fallback_value' => false,
            )
        ];
    }
}
