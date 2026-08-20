<?php

declare(strict_types=1);

namespace App\Form\Database;

use App\Entity\Database\Address;
use App\Entity\Database\Enums\AddressTypes;
use App\Entity\Database\Enums\Studies;
use App\Entity\Database\MailingList;
use App\Entity\Database\ProspectiveMember;
use App\Form\DataTransformer\LowercaseTransformer;
use App\Validator\Database\DeliverableEmailAddress;
use App\Validator\Database\StudentNumber;
use DateTime;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_filter;
use function Symfony\Component\Translation\t;

/**
 * Public sign-up, served from join.gewis.nl.
 */
class RegistrationType extends AbstractType
{
    public function __construct(private readonly TranslatorInterface $translator)
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
        $builder
            ->add(
                'lastName',
                TextType::class,
                [
                    'label' => t('Last Name'),
                    'constraints' => [
                        new Assert\NotBlank(),
                        new Assert\Length(
                            min: 2,
                            max: 32,
                        ),
                    ],
                ],
            )
            ->add(
                'middleName',
                TextType::class,
                [
                    'label' => t('Last Name Prepositional Particle'),
                    'required' => false,
                    // The column is NOT NULL: an untouched optional field is an empty string, not a missing one.
                    'empty_data' => '',
                    // A member without a particle has an empty one, which is shorter than any real particle.
                    'constraints' => [
                        new Assert\When(
                            expression: "'' !== value",
                            constraints: [
                                new Assert\Length(
                                    min: 2,
                                    max: 32,
                                ),
                            ],
                        ),
                    ],
                ],
            )
            ->add(
                'initials',
                TextType::class,
                [
                    'label' => t('Initial(s)'),
                    'constraints' => [
                        new Assert\NotBlank(),
                        new Assert\Length(
                            min: 1,
                            max: 16,
                        ),
                    ],
                ],
            )
            ->add(
                'firstName',
                TextType::class,
                [
                    'label' => t('First Name'),
                    'constraints' => [
                        new Assert\NotBlank(),
                        new Assert\Length(
                            min: 1,
                            max: 32,
                        ),
                    ],
                ],
            )
            ->add(
                'studentNumber',
                TextType::class,
                [
                    'label' => t('TU/e student number'),
                    'constraints' => [
                        new Assert\NotBlank(),
                        new StudentNumber(),
                    ],
                ],
            )
            // Grouped by category, which is why this is not an `EnumType`; the choices are the enum cases all the
            // same.
            ->add(
                'study',
                ChoiceType::class,
                [
                    'label' => t('Study'),
                    'placeholder' => t('Select a study'),
                    'choices' => StudyChoices::grouped(),
                    // Resolved while the view is built rather than at build time, so the footnoted labels follow the
                // request locale.
                    'choice_label' => fn (Studies $study): Studies|string => StudyChoices::labelWithFootnote(
                        $study,
                        $this->translator,
                    ),
                    'choice_value' => static fn (?Studies $study): ?string => $study?->value,
                    'invalid_message' => 'Select an existing study.',
                    'constraints' => [new Assert\NotNull()],
                ],
            )
            ->add(
                'email',
                EmailType::class,
                [
                    'label' => t('E-mail Address'),
                    'constraints' => [
                        new Assert\NotBlank(),
                        new Assert\Email(),
                        new Assert\Regex(
                            pattern: '/\\.tue\\.nl$/i',
							// phpcs:ignore -- user-visible strings should not be split
						message: 'You cannot use your TU/e (student) e-mail address because if you leave or stop studying, we can no longer reach you about important announcements.',
                            match: false,
                        ),
                        new DeliverableEmailAddress(),
                    ],
                ],
            )
            ->add(
                'birth',
                DateType::class,
                [
                    'label' => t('Birthdate'),
                    'widget' => 'single_text',
                    'constraints' => [
                        new Assert\NotNull(),
                        new Assert\LessThanOrEqual(
                            value: new DateTime('-10 years'),
                            message: t('Are you sure that you are younger than 10 years?')->getMessage(),
                        ),
                    ],
                ],
            )
            // A prospective member stores its address in its own columns rather than in an `Address`, so the
            // registration hands the composed address to the service instead of mapping it onto the entity.
            ->add(
                'address',
                AddressType::class,
                [
                    'label' => false,
                    'mapped' => false,
                    'include_type' => false,
                ],
            )
            ->add(
                'lists',
                ChoiceType::class,
                [
                    'label' => t('Mailing lists'),
                    'mapped' => false,
                    'expanded' => true,
                    'multiple' => true,
                    'choices' => $options['mailing_lists'],
                    'choice_label' => static fn (MailingList $list): object => new MailingListLabel($list),
                    'choice_value' => static fn (?MailingList $list): ?string => $list?->getName(),
                    'data' => array_filter(
                        $options['mailing_lists'],
                        static fn (MailingList $list): bool => $list->getDefaultSub(),
                    ),
                ],
            )
            ->add(
                'agreed',
                CheckboxType::class,
                [
                    'mapped' => false,
					// phpcs:ignore -- user-visible strings should not be split
				'label' => t('I hereby declare to have filled in the form truthfully and agree to be a member of Study Association GEWIS. I am familiar with the contents of the Articles of Association and Internal Regulations. I hereby give also Gemeenschap van Wiskunde en Informatica Studenten (GEWIS) permission to process my personal data according to its Privacy Policy.'),
                    'constraints' => [
                        new Assert\IsTrue(
                            message: t('You cannot become a member of the association without agreeing to the terms.')
                                ->getMessage(),
                        ),
                    ],
                ],
            )
            ->add(
                'agreedStripe',
                CheckboxType::class,
                [
                    'mapped' => false,
                    // phpcs:ignore -- user-visible strings should not be split
                    'label' => t('I hereby authorise Stripe to process my personal data according to its privacy policy to pay the one-time membership fee.'),
                    'constraints' => [
                        new Assert\IsTrue(
                            message: t('To pay the membership fee you must accept Stripe\'s privacy policy.')
                                ->getMessage(),
                        ),
                    ],
                ],
            )
            ->add(
                'submit',
                SubmitType::class,
                ['label' => t('Go to checkout')],
            );

        $builder->get('email')->addModelTransformer(new LowercaseTransformer());

        // The address is always a student address here, and the field that would say so is not rendered.
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            static function (FormEvent $event): void {
                $address = $event->getForm()->get('address')->getData();

                if (!$address instanceof Address) {
                    return;
                }

                $address->setType(AddressTypes::Student);
            },
        );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProspectiveMember::class,
            'mailing_lists' => [],
        ]);

        $resolver->setAllowedTypes(
            'mailing_lists',
            'array',
        );
    }
}
