<?php

declare(strict_types=1);

namespace App\Form\Database;

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function Symfony\Component\Translation\t;

/**
 * Confirmation of a member removal. Which button was clicked decides the outcome; the form itself exists so that the
 * confirmation carries a token.
 */
class DeleteMemberType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder->add(
            'submit_yes',
            SubmitType::class,
            ['label' => t('Yes')],
        );
        $builder->add(
            'submit_no',
            SubmitType::class,
            ['label' => t('No')],
        );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null]);
    }
}
