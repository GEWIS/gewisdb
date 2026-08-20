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
 * Confirmation of deleting a decision.
 *
 * Both answers submit the form, so a valid submission is not by itself a yes: the caller has to ask which button was
 * clicked before it deletes anything.
 */
class DeleteDecisionType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder
            ->add(
                'submit_yes',
                SubmitType::class,
                ['label' => t('Yes')],
            )
            ->add(
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
