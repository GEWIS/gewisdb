<?php

declare(strict_types=1);

namespace Database\Form;

use Laminas\Form\Element\Submit;
use Laminas\Form\Element\Text;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;

/**
 * @psalm-import-type QuerySaveFormType from Query
 * @extends Query<QuerySaveFormType>
 */
class QuerySave extends Query implements InputFilterProviderInterface
{
    public function __construct(private readonly Translator $translator)
    {
        parent::__construct($this->translator);

        $this->add([
            'name' => 'name',
            'type' => Text::class,
            'options' => [
                'label' => $this->translator->translate('Name'),
            ],
        ]);

        $this->add([
            'name' => 'submit_save',
            'type' => Submit::class,
            'attributes' => [
                'label' => $this->translator->translate('Save'),
                'value' => $this->translator->translate('Save'),
            ],
        ]);
    }

    /**
     * @return array
     */
    public function getInputFilterSpecification(): array
    {
        $filter = parent::getInputFilterSpecification();
        $filter += [
            'name' => [
                'required' => true,
            ],
        ];

        return $filter;
    }
}
