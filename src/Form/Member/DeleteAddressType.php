<?php

declare(strict_types=1);

namespace App\Form\Member;

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function Symfony\Component\Translation\t;

/**
 * Confirmation of an address removal. Buttons are not validated, so the caller acts on `submit_yes` having been
 * clicked rather than on the form being valid.
 */
class DeleteAddressType extends AbstractType
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
        $builder->add('submit_yes', SubmitType::class, ['label' => t('Yes')]);
        $builder->add('submit_no', SubmitType::class, ['label' => t('No')]);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null]);
    }
}
