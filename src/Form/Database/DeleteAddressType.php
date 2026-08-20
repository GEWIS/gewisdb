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
 * Confirmation of an address removal. The caller must check isValid() before acting on `submit_yes`: the form
 * carries the CSRF token, and a branch on the button alone would never validate it.
 */
class DeleteAddressType extends AbstractType
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
