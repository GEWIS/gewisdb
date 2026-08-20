<?php

declare(strict_types=1);

namespace App\Form\User;

use Override;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

use function Symfony\Component\Translation\t;

/**
 * Credential form. It only shapes and validates the submitted credentials; verifying them is not its concern.
 */
class LoginType extends AbstractType
{
    public function __construct(
        #[Autowire(env: 'default::DEMO_CREDENTIALS_USERNAME')]
        private readonly ?string $demoUsername = null,
        #[Autowire(env: 'default::DEMO_CREDENTIALS_PASSWORD')]
        private readonly ?string $demoPassword = null,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $demoUsername = $this->demoUsername ?? '';
        $demoPassword = $this->demoPassword ?? '';

        // In demo mode the credentials come from the environment: the login is shown but fixed, and the password is
        // carried along in a hidden field instead of being asked for.
        if ('' !== $demoPassword) {
            $builder->add(
                'login',
                TextType::class,
                [
                    'label' => t('Login'),
                    'data' => $demoUsername,
                    'attr' => ['readonly' => true],
                    'constraints' => self::loginConstraints(),
                ],
            );

            $builder->add(
                'password',
                HiddenType::class,
                [
                    'label' => t('Password'),
                    'data' => $demoPassword,
                    'constraints' => [new Assert\NotBlank()],
                ],
            );
        } else {
            $builder->add(
                'login',
                TextType::class,
                [
                    'label' => t('Login'),
                    'constraints' => self::loginConstraints(),
                ],
            );

            $builder->add(
                'password',
                PasswordType::class,
                [
                    'label' => t('Password'),
                    'constraints' => [new Assert\NotBlank()],
                ],
            );
        }

        $builder->add(
            'submit',
            SubmitType::class,
            ['label' => t('Login')],
        );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            // The submitted credentials never reach a controller — the firewall intercepts them — so the token this
            // form renders is the one the firewall validates, under the identifier it uses.
            'csrf_token_id' => 'authenticate',
        ]);
    }

    /**
     * @return Constraint[]
     */
    private static function loginConstraints(): array
    {
        return [
            new Assert\NotBlank(),
            new Assert\Length(
                min: 3,
                max: 32,
            ),
            new Assert\Regex(pattern: '/^[a-zA-Z0-9]*$/'),
        ];
    }
}
