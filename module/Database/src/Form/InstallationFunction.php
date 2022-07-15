<?php

namespace Database\Form;

use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;

class InstallationFunction extends Form implements InputFilterProviderInterface
{
    public function __construct()
    {
        parent::__construct();

        $this->add([
            'name' => 'name',
            'type' => 'text',
            'options' => [
                'label' => 'Functienaam',
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Maak functie',
            ],
        ]);
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification(): array
    {
        return [];
    }
}
