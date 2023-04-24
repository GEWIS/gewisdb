<?php

declare(strict_types=1);

namespace Database\Form;

use Laminas\Form\Element\Select;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;

class QueryExport extends Query implements InputFilterProviderInterface
{
    public function __construct(private readonly Translator $translator)
    {
        parent::__construct($this->translator);

        $this->add([
            'name' => 'type',
            'type' => Select::class,
            'options' => [
                'value_options' => [
                    'csv' => $this->translator->translate('CSV'),
                ],
            ],
        ]);

        $this->get('submit')->setAttribute('value', 'export');
        $this->get('submit')->setLabel($this->translator->translate('Export'));
    }

    /**
     * @return array
     */
    public function getInputFilterSpecification(): array
    {
        $filter = parent::getInputFilterSpecification();
        $filter += [
            'type' => [
                'required' => true,
            ],
        ];

        return $filter;
    }
}
