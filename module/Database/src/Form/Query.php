<?php

namespace Database\Form;

use Laminas\Mvc\I18n\Translator;
use Laminas\Form\Element\{
    Submit,
    Textarea,
};
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;

/**
 * @psalm-import-type InputFilterSpecification from \Laminas\InputFilter\InputFilterInterface
 * @psalm-import-type CollectionSpecification from \Laminas\InputFilter\InputFilterInterface
 */
class Query extends Form implements InputFilterProviderInterface
{
    public function __construct(private readonly Translator $translator)
    {
        parent::__construct();

        $this->add([
            'name' => 'query',
            'type' => Textarea::class,
            'options' => [
                'label' => $this->translator->translate('Query'),
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => $this->translator->translate('Execute'),
            ],
            'options' => [
                'label' => $this->translator->translate('Execute'),
            ],
        ]);
    }

    /**
     * Specification of input filter.
     *
     * @psalm-return InputFilterSpecification|CollectionSpecification
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'query' => [
                'required' => true,
            ],
        ];
    }
}
