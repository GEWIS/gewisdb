<?php

declare(strict_types=1);

namespace App\Form\User;

use App\Entity\User\User;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

use function Symfony\Component\Translation\t;

class UserCreateType extends AbstractType
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
            'login',
            TextType::class,
            [
                'label' => t('Login'),
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(
                        min: 3,
                        max: 32,
                    ),
                    new Assert\Regex(pattern: '/^[a-zA-Z0-9]*$/'),
                ],
            ],
        );

        // User::$password holds a hash, so the plaintext is deliberately not mapped; the caller hashes the submitted
        // value and sets it on the entity.
        $builder->add(
            'password',
            RepeatedType::class,
            [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_name' => 'password',
                'second_name' => 'password_verify',
                'first_options' => ['label' => t('Wachtwoord')],
                'second_options' => ['label' => t('Controleer wachtwoord')],
                'invalid_message' => 'The two given tokens do not match',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(min: 10),
                ],
            ],
        );

        $builder->add(
            'submit',
            SubmitType::class,
            ['label' => t('Maak gebruiker aan')],
        );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }
}
