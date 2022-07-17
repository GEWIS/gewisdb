<?php

namespace Database\Form;

use Application\Model\Enums\MembershipTypes;
use Laminas\Form\Element\{
    Radio,
    Submit,
};
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;

class MemberType extends Form implements InputFilterProviderInterface
{
    public function __construct()
    {
        parent::__construct();

        $this->add([
            'name' => 'type',
            'type' => Radio::class,
            'options' => [
                'label' => 'Lidmaatschapstype',
                'value_options' => [
                    MembershipTypes::Ordinary->value => 'Gewoon lid - Ingeschreven bij faculteit M&CS',
                    MembershipTypes::External->value => 'Extern lid - Speciaal toegelaten door het bestuur',
                    MembershipTypes::Graduate->value => 'Afgestudeerde - Was lid en is speciaal toegelaten door het bestuur',
                    MembershipTypes::Honorary->value => 'Erelid - Speciaal benoemd door de ALV',
                ],
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => 'Wijzig type',
            ],
        ]);
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'type' => [
                'required' => true,
            ],
        ];
    }
}
