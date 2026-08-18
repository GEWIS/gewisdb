<?php

declare(strict_types=1);

namespace App\Form\Join;

use App\Entity\Join\ProspectiveMember;
use App\Entity\Mailing\MailingList;
use App\Entity\Member\Address;
use App\Entity\Member\Enums\AddressTypes;
use App\Entity\Member\Enums\Studies;
use App\Form\DataTransformer\LowercaseTransformer;
use App\Form\DataTransformer\StringToEnumTransformer;
use App\Form\Member\AddressType;
use App\Form\Member\StudyChoices;
use App\Validator\Member\DeliverableEmailAddress;
use App\Validator\Member\StudentNumber;
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
            ->add('lastName', TextType::class, [
                'label' => t('Last Name'),
                'constraints' => [new Assert\NotBlank(), new Assert\Length(min: 2, max: 32)],
            ])
            ->add('middleName', TextType::class, [
                'label' => t('Last Name Prepositional Particle'),
                'required' => false,
                'constraints' => [new Assert\Length(min: 2, max: 32)],
            ])
            ->add('initials', TextType::class, [
                'label' => t('Initial(s)'),
                'constraints' => [new Assert\NotBlank(), new Assert\Length(min: 1, max: 16)],
            ])
            ->add('firstName', TextType::class, [
                'label' => t('First Name'),
                'constraints' => [new Assert\NotBlank(), new Assert\Length(min: 1, max: 32)],
            ])
            ->add('studentNumber', TextType::class, [
                'label' => t('TU/e student number'),
                'constraints' => [new Assert\NotBlank(), new StudentNumber()],
            ])
            ->add('study', ChoiceType::class, [
                'label' => t('Study'),
                'placeholder' => t('Select a study'),
                'choices' => StudyChoices::grouped(),
                // Resolved while the view is built rather than at build time, so the labels follow the request locale.
                'choice_label' => fn (string $study): string => StudyChoices::label($study, $this->translator, true),
                'invalid_message' => t('Select an existing study.'),
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('email', EmailType::class, [
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
            ])
            ->add('birth', DateType::class, [
                'label' => t('Birthdate'),
                'widget' => 'single_text',
                'constraints' => [
                    new Assert\NotNull(),
                    new Assert\LessThanOrEqual(
                        value: new DateTime('-10 years'),
                        message: t('Are you sure that you are younger than 10 years?')->getMessage(),
                    ),
                ],
            ])
            ->add('address', AddressType::class, [
                'label' => false,
                'include_type' => false,
            ])
            ->add('lists', ChoiceType::class, [
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
            ])
            ->add('agreed', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new Assert\IsTrue(
                        message: t('You cannot become a member of the association without agreeing to the terms.')
                            ->getMessage(),
                    ),
                ],
            ])
            ->add('agreedStripe', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new Assert\IsTrue(
                        message: t('To pay the membership fee you must accept Stripe\'s privacy policy.')->getMessage(),
                    ),
                ],
            ])
            ->add('submit', SubmitType::class, ['label' => t('Go to checkout')]);

        $builder->get('email')->addModelTransformer(new LowercaseTransformer());
        $builder->get('study')->addModelTransformer(new StringToEnumTransformer(Studies::class));

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

        $resolver->setAllowedTypes('mailing_lists', 'array');
    }
}
