<?php

declare(strict_types=1);

namespace Database\Form;

use Laminas\Form\Element\Submit;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;

class DeleteList extends Form implements InputFilterProviderInterface
{
    public function __construct(private readonly Translator $translator)
    {
        parent::__construct();

        $this->add([
            'name' => 'submit_yes',
            'type' => Submit::class,
            'attributes' => [
                'value' => $this->translator->translate('Yes'),
            ],
        ]);

        $this->add([
            'name' => 'submit_no',
            'type' => Submit::class,
            'attributes' => [
                'value' => $this->translator->translate('No'),
            ],
        ]);
    }

    public function getInputFilterSpecification(): array
    {
        return [
            'submit_yes' => ['required' => true],
        ];
    }
}
