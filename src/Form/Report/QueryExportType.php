<?php

declare(strict_types=1);

namespace App\Form\Report;

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

use function Symfony\Component\Translation\t;

/**
 * Form that re-submits an already executed query to obtain it as a downloadable file.
 */
class QueryExportType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        // This form sits next to the result table and carries the executed query along, so the query is not typed
        // here but replayed from a hidden field.
        $builder->add(
            'query',
            HiddenType::class,
            [
                'required' => true,
                'trim' => false,
                'empty_data' => '',
                'constraints' => [new Assert\NotBlank()],
            ],
        );

        $builder->add(
            'name',
            HiddenType::class,
            ['required' => false],
        );

        $builder->add(
            'type',
            ChoiceType::class,
            [
                'choices' => ['CSV' => 'csv'],
                'required' => true,
                'constraints' => [new Assert\NotBlank()],
            ],
        );

        $builder->add(
            'submit',
            SubmitType::class,
            ['label' => t('Export')],
        );
    }

    #[Override]
    public function getParent(): string
    {
        return QueryType::class;
    }
}
