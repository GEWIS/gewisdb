<?php

declare(strict_types=1);

namespace App\Form\Database;

use App\Entity\Database\Enums\InstallationFunctions;
use App\Form\DataTransformer\StringToEnumTransformer;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A single member being installed in an organ, in one function.
 *
 * The function is filled in by the page, not typed, so it stays a hidden field. The data of this type is a plain
 * array, keyed `member` and `function`.
 */
class InstallationType extends AbstractType
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
            'function',
            HiddenType::class,
            [
                'invalid_message' => 'Select an existing function.',
            ],
        );

        $builder->get('function')->addModelTransformer(
            new StringToEnumTransformer(InstallationFunctions::class),
        );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
