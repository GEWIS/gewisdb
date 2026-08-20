<?php

declare(strict_types=1);

namespace App\Form\Database;

use App\Form\DataTransformer\MemberLookupTransformer;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function Symfony\Component\Translation\t;

/**
 * Picks a single member, by membership number.
 *
 * The data of this type is the member itself. Decision forms that embed it therefore no longer have to look the
 * member up after the fact, which is what {@see \App\Form\DataTransformer\MemberLookupTransformer} now does.
 */
class MemberLookupType extends AbstractType
{
    public function __construct(private readonly MemberLookupTransformer $memberLookupTransformer)
    {
    }

    /**
     * @param array<string, mixed> $options
     */
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        if ($options['include_name']) {
            // Not part of the member's identity; it is only the source for the autocomplete that fills in `lidnr`.
            $builder->add(
                'name',
                TextType::class,
                [
                    'label' => t('Lid'),
                    'required' => false,
                ],
            );
        }

        $builder->add(
            'lidnr',
            HiddenType::class,
        );

        $builder->addModelTransformer($this->memberLookupTransformer);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'include_name' => true,
            'invalid_message' => 'Select an existing member.',
        ]);

        $resolver->setAllowedTypes(
            'include_name',
            'bool',
        );
    }
}
