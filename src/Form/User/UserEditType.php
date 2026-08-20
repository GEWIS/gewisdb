<?php

declare(strict_types=1);

namespace App\Form\User;

use App\Entity\User\User;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

use function Symfony\Component\Translation\t;

class UserEditType extends AbstractType
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
            'password',
            RepeatedType::class,
            self::passwordOptions(true),
        );
        $builder->add(
            'submit',
            SubmitType::class,
            ['label' => t('Wijzig gebruiker')],
        );

        // A non-local user authenticates externally and has no password to change here, so the fields are still
        // rendered but neither required nor submittable.
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            static function (PreSetDataEvent $event): void {
                $user = $event->getData();

                if (
                    !$user instanceof User
                    || $user->isLocal()
                ) {
                    return;
                }

                $event->getForm()->add(
                    'password',
                    RepeatedType::class,
                    self::passwordOptions(false),
                );
            },
        );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function passwordOptions(bool $passwordNeeded): array
    {
        return [
            'type' => PasswordType::class,
            // User::$password holds a hash, so the plaintext is deliberately not mapped; the caller hashes the
            // submitted value and sets it on the entity.
            'mapped' => false,
            'required' => $passwordNeeded,
            'disabled' => !$passwordNeeded,
            'first_name' => 'password',
            'second_name' => 'password_verify',
            'first_options' => ['label' => t('Wachtwoord')],
            'second_options' => ['label' => t('Controleer wachtwoord')],
            'invalid_message' => 'The two given tokens do not match',
            'constraints' => $passwordNeeded
                ? [
                    new Assert\NotBlank(),
                    new Assert\Length(min: 10),
                ]
                : [],
        ];
    }
}
