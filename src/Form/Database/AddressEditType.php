<?php

declare(strict_types=1);

namespace App\Form\Database;

use App\Entity\Database\Address;
use App\Entity\Database\Enums\PostalRegions;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

use function Symfony\Component\Translation\t;

/**
 * The page on which the secretary adds or edits one address of a member. Which of the three addresses is being
 * edited follows from the page, so the caller sets the type on the address before binding it.
 */
class AddressEditType extends AbstractType
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
            'country',
            EnumType::class,
            [
                'label' => t('Postal Region'),
                'class' => PostalRegions::class,
                'placeholder' => t('Select Postal Region'),
                'invalid_message' => 'Select an existing postal region.',
                'constraints' => [new Assert\NotNull()],
                // The data mapper writes to the address before the constraint above is checked, and the setter does not
            // accept null, so an empty submission would be a TypeError rather than the violation it should be.
                'setter' => static function (Address $address, ?PostalRegions $country): void {
                    if (null === $country) {
                        return;
                    }

                    $address->setCountry($country);
                },
            ],
        );

        $builder->add(
            'street',
            TextType::class,
            [
                'label' => t('Street'),
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(
                        min: 2,
                        max: 32,
                    ),
                ],
            ],
        );

        $builder->add(
            'number',
            TextType::class,
            [
                'label' => t('House Number'),
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Regex(pattern: '/^[1-9]\d*(?:[ \/\-\#\.]?[a-zA-Z0-9]+)?$/'),
                ],
            ],
        );

        $builder->add(
            'postalCode',
            TextType::class,
            [
                'label' => t('Postal Code'),
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(
                        min: 2,
                        max: 16,
                    ),
                ],
            ],
        );

        $builder->add(
            'city',
            TextType::class,
            [
                'label' => t('City'),
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(
                        min: 2,
                        max: 32,
                    ),
                ],
            ],
        );

        // TODO: phone number validation
        // Optional, as it is on the registration form: the column is NOT NULL, so an untouched field is an empty
        // string rather than a missing one. Requiring it here would leave the secretary unable to save any edit to an
        // address that has no phone number, which most of them do not.
        $builder->add(
            'phone',
            TextType::class,
            [
                'label' => t('Phone Number'),
                'required' => false,
                'empty_data' => '',
            ],
        );

        $builder->add(
            'submit',
            SubmitType::class,
            ['label' => t('Update Address')],
        );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Address::class]);
    }
}
