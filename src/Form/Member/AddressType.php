<?php

declare(strict_types=1);

namespace App\Form\Member;

use App\Entity\Member\Address;
use App\Entity\Member\Enums\AddressTypes;
use App\Entity\Member\Enums\PostalRegions;
use App\Form\DataTransformer\StringToEnumTransformer;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

use function Symfony\Component\Translation\t;

/**
 * One of the addresses of a member.
 */
class AddressType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        if ($options['include_type']) {
            // Which of the three addresses this is follows from the page, not from the person filling it in.
            $builder->add('type', HiddenType::class, [
                'invalid_message' => t('Select an existing address type.'),
            ]);

            $builder->get('type')->addModelTransformer(new StringToEnumTransformer(AddressTypes::class));
        }

        $builder->add('country', ChoiceType::class, [
            'label' => t('Postal Region'),
            'placeholder' => t('Select Postal Region'),
            'choices' => PostalRegions::formValues(),
            'invalid_message' => t('Select an existing postal region.'),
        ]);

        $builder->add('street', TextType::class, [
            'label' => t('Street'),
            'constraints' => [
                new NotBlank(),
                new Length(
                    min: 1,
                    max: 32,
                ),
            ],
        ]);

        $builder->add('number', TextType::class, [
            'label' => t('House Number'),
            'constraints' => [
                new NotBlank(),
                new Regex(pattern: '/^[1-9]\d*(?:[ \/\-\#\.]?[a-zA-Z0-9]+)?$/'),
            ],
        ]);

        $builder->add('postalCode', TextType::class, [
            'label' => t('Postal Code'),
            'constraints' => [
                new NotBlank(),
                new Length(
                    min: 2,
                    max: 16,
                ),
            ],
        ]);

        $builder->add('city', TextType::class, [
            'label' => t('City'),
            'constraints' => [
                new NotBlank(),
                new Length(
                    min: 1,
                    max: 32,
                ),
            ],
        ]);

        $builder->add('phone', TextType::class, [
            'label' => t('Phone Number'),
            'required' => false,
        ]);

        $builder->get('country')->addModelTransformer(new StringToEnumTransformer(PostalRegions::class));

        // A new address starts out in the Netherlands; one that already exists keeps the region it has.
        $builder->get('country')->addEventListener(
            FormEvents::PRE_SET_DATA,
            static function (FormEvent $event): void {
                if (null !== $event->getData()) {
                    return;
                }

                $event->setData(PostalRegions::Netherlands);
            },
        );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Address::class,
            // A form that fixes the address type itself, or that edits an address that already has one, leaves the
            // hidden field out.
            'include_type' => true,
        ]);

        $resolver->setAllowedTypes('include_type', 'bool');
    }
}
