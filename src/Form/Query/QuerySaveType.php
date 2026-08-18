<?php

declare(strict_types=1);

namespace App\Form\Query;

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

use function Symfony\Component\Translation\t;

/**
 * Form for storing a query under a name, on top of the query itself.
 *
 * The form yields an array; turning that into a `SavedQuery` (looking up an existing query by name, or creating a new
 * one) is done by `App\Service\Query\QueryService::save()`.
 */
class QuerySaveType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */

    /**
     * @param array<string, mixed> $options
     */
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder->add('category', TextType::class, [
            'label' => t('Category'),
            'required' => false,
            'empty_data' => '',
        ]);

        $builder->add('name', TextType::class, [
            'label' => t('Name'),
            'required' => true,
            'empty_data' => '',
            'constraints' => [new Assert\NotBlank()],
        ]);

        $builder->add('submit_save', SubmitType::class, ['label' => t('Save')]);
    }

    #[Override]
    public function getParent(): string
    {
        return QueryType::class;
    }
}
