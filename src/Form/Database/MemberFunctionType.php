<?php

declare(strict_types=1);

namespace App\Form\Database;

use App\Entity\Database\Enums\InstallationFunctions;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

use function array_filter;
use function array_values;
use function Symfony\Component\Translation\t;

/**
 * A single member together with the function they are given, picked from a list.
 *
 * Legacy functions are never offered; they describe past installations only. Ordinary and inactive membership of an
 * organ are left out where the page adds those itself, in which case there is no sensible pre-selection either. The
 * data of this type is a plain array, keyed `member` and `function`.
 */
class MemberFunctionType extends AbstractType
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
        );

        $functionOptions = [
            'label' => t('Functie'),
            'class' => InstallationFunctions::class,
            'choices' => $this->functions($options['include_administrative']),
            'placeholder' => false,
            'constraints' => [new NotNull()],
        ];

        if ($options['include_administrative']) {
            $functionOptions['data'] = InstallationFunctions::Member;
        }

        $builder->add(
            'function',
            EnumType::class,
            $functionOptions,
        );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'include_administrative' => true,
        ]);

        $resolver->setAllowedTypes(
            'include_administrative',
            'bool',
        );
    }

    /**
     * @return InstallationFunctions[]
     */
    private function functions(bool $includeAdministrative): array
    {
        return array_values(array_filter(
            InstallationFunctions::cases(),
            static function (InstallationFunctions $function) use ($includeAdministrative): bool {
                if ($function->isLegacy()) {
                    return false;
                }

                return $includeAdministrative || !$function->isAdministrative();
            },
        ));
    }
}
