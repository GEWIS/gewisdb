<?php

namespace Database\Form\Board;

use Database\Form\AbstractDecision;
use Database\Form\Fieldset;
use Laminas\InputFilter\InputFilterProviderInterface;

class Discharge extends AbstractDecision implements InputFilterProviderInterface
{
    public function __construct(Fieldset\Meeting $meeting, Fieldset\SubDecision $installation)
    {
        parent::__construct($meeting);

        $this->add(clone $installation);

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => 'Dechargeer bestuurder'
            )
        ));
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification(): array
    {
        return array(
        );
    }
}
