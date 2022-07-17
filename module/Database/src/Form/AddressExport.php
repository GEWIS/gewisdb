<?php

namespace Database\Form;

use Laminas\Form\Element\{
    Checkbox,
    Submit,
};
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;

class AddressExport extends Form implements InputFilterProviderInterface
{
    public function __construct()
    {
        parent::__construct();

        $this->add([
            'name' => 'supremum',
            'type' => Checkbox::class,
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => 'Exporteer',
            ],
        ]);

        $this->get('submit')->setAttribute('value', 'Exporteer');
        $this->get('submit')->setLabel('Exporteer');
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification(): array
    {
        return [];
    }
}
