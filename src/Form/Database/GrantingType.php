<?php

declare(strict_types=1);

namespace App\Form\Database;

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Carries the grantee and the expiration of the key code granting a withdrawal points at.
 *
 * Both are filled in by the page from the granting that was picked, and neither is stored: the expiration is only
 * there so a withdrawal can be checked against it. The data of this type is therefore a plain array, keyed `member`
 * and `until`.
 */
class GrantingType extends AbstractType
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
            'member',
            MemberLookupType::class,
            [
                'include_name' => false,
            ],
        );

        $builder->add(
            'until',
            HiddenType::class,
            [
                'constraints' => [
                    new NotBlank(),
                    new Date(),
                ],
            ],
        );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'invalid_message' => 'Select an existing key code granting.',
        ]);
    }
}
