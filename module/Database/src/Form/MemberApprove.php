<?php

namespace Database\Form;

use Application\Model\Enums\MembershipTypes;
use Laminas\Form\Element\Checkbox;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;

class MemberApprove extends MemberType
{
    public function __construct()
    {
        parent::__construct();

        $this->add([
            'name' => 'updatedata',
            'type' => Checkbox::class,
            'options' => [
                'label' => 'Update name with data from TU/e',
                'use_hidden_element' => false,
            ],
        ]);
    }
}
