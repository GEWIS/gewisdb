<?php

declare(strict_types=1);

namespace App\Form\Database;

use App\Entity\Database\Member;
use App\Entity\Database\RenewalLink;
use App\Form\DataTransformer\LowercaseTransformer;
use App\Form\DataTransformer\OptInTransformer;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

use function array_merge;
use function Symfony\Component\Translation\t;

/**
 * Renewal of a graduate membership, filled in by the member through a renewal link. Everything but the contact
 * details is fixed: those fields are disabled so that what is submitted for them cannot alter the member.
 */
class MemberRenewalType extends AbstractType
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
            'lastName',
            TextType::class,
            self::fixedOptions(t('Last Name')),
        );
        $builder->add(
            'middleName',
            TextType::class,
            self::fixedOptions(t('Last Name Prepositional Particle')),
        );
        $builder->add(
            'initials',
            TextType::class,
            self::fixedOptions(t('Initial(s)')),
        );
        $builder->add(
            'firstName',
            TextType::class,
            self::fixedOptions(t('First Name')),
        );

        $builder->add(
            'email',
            EmailType::class,
            [
                'label' => t('E-mail Address'),
                'constraints' => [new Assert\NotBlank()],
            ],
        );

        // The new expiration follows from the renewal link, it is shown but never submitted.
        $builder->add(
            'expiration',
            DateType::class,
            array_merge(
                self::fixedOptions(t('Renew until')),
                [
                    'widget' => 'single_text',
                    'mapped' => false,
                    'data' => $options['renewal_link']->getNewExpiration(),
                ],
            ),
        );

        $builder->add(
            'supremum',
            CheckboxType::class,
            [
                'label' => t('I\'d like to receive the Supremum magazine 3 times a year'),
                'required' => false,
            ],
        );

        $builder->add(
            'privacy',
            CheckboxType::class,
            [
                'label' => t(
					// phpcs:ignore -- user-visible strings should not be split
				'I have read the privacy statement of Gemeenschap van Wiskunde en Informatica Studenten and consent to the processing of my data.',
                ),
                'mapped' => false,
                'constraints' => [new Assert\IsTrue(message: 'You have to consent to processing your data')],
            ],
        );

        $builder->add(
            'agreed',
            CheckboxType::class,
            [
                'label' => t(
					// phpcs:ignore -- user-visible strings should not be split
				'I am familiar with the contents of the Articles of Association and the Internal Regulations of GEWIS and I would like to renew my status as a graduate',
                ),
                'mapped' => false,
                'constraints' => [
                    new Assert\IsTrue(
						// phpcs:ignore -- user-visible strings should not be split
					message: 'You have to agree to the Articles of Association and the Internal Regulations',
                    ),
                ],
            ],
        );

        $builder->add(
            'submit',
            SubmitType::class,
            ['label' => t('Renew')],
        );

        $builder->get('email')->addModelTransformer(new LowercaseTransformer());
        $builder->get('supremum')->addModelTransformer(new OptInTransformer());
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Member::class]);

        $resolver->setRequired('renewal_link');
        $resolver->setAllowedTypes(
            'renewal_link',
            RenewalLink::class,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function fixedOptions(TranslatableMessage $label): array
    {
        return [
            'label' => $label,
            'disabled' => true,
            'attr' => ['readonly' => true],
        ];
    }
}
