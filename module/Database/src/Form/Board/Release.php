<?php

namespace Database\Form\Board;

use Database\Form\AbstractDecision;
use Database\Form\Fieldset;
use Laminas\InputFilter\InputFilterProviderInterface;

class Release extends AbstractDecision implements InputFilterProviderInterface
{
    protected $service;

    public function __construct(Fieldset\Meeting $meeting, Fieldset\SubDecision $installation)
    {
        parent::__construct($meeting);

        $this->add(clone $installation);

        $this->add([
            'name' => 'date',
            'type' => 'date',
            'options' => [
                'label' => 'Van kracht per',
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Dechargeer bestuurder',
            ],
        ]);
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'date' => [
                'required' => true,
            ],
        ];
    }
}
