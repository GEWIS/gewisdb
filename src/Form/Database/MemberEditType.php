<?php

declare(strict_types=1);

namespace App\Form\Database;

use App\Entity\Database\Enums\Studies;
use App\Entity\Database\Member;
use App\Form\DataTransformer\LowercaseTransformer;
use App\Validator\Database\StudentNumber;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

use function Symfony\Component\Translation\t;

/**
 * The member data as the secretary maintains it.
 */
class MemberEditType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder->add('lastName', TextType::class, [
            'label' => t('Last Name'),
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\Length(
                    min: 2,
                    max: 32,
                ),
            ],
        ]);

        $builder->add('middleName', TextType::class, [
            'label' => t('Last Name Prepositional Particle'),
            'required' => false,
            // The column is NOT NULL: an untouched optional field is an empty string, not a missing one.
            'empty_data' => '',
            'constraints' => [
                // A member without a particle has an empty one, which is shorter than any real particle.
                new Assert\When(
                    expression: "'' !== value",
                    constraints: [new Assert\Length(min: 2, max: 32)],
                ),
            ],
        ]);

        $builder->add('initials', TextType::class, [
            'label' => t('Initial(s)'),
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\Length(
                    min: 1,
                    max: 16,
                ),
            ],
        ]);

        $builder->add('firstName', TextType::class, [
            'label' => t('First Name'),
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\Length(
                    min: 2,
                    max: 32,
                ),
            ],
        ]);

        $builder->add('studentNumber', TextType::class, [
            'label' => t('TU/e student number'),
            'required' => false,
            'empty_data' => null,
            'constraints' => [new StudentNumber()],
        ]);

        $builder->add('email', EmailType::class, [
            'label' => t('E-mail Address'),
            'required' => false,
            'empty_data' => null,
            'constraints' => [new Assert\Email()],
        ]);

        $builder->add('birth', DateType::class, [
            'label' => t('Birthdate'),
            'widget' => 'single_text',
            'constraints' => [new Assert\NotNull()],
        ]);

        // Grouped by category, which is why this is not an `EnumType`; the choices are the enum cases all the same.
        $builder->add('study', ChoiceType::class, [
            'label' => t('Study'),
            'placeholder' => t('Select a study'),
            'choices' => StudyChoices::grouped(withSpecialCases: true),
            'choice_label' => static fn (Studies $study): Studies => $study,
            'choice_value' => static fn (?Studies $study): ?string => $study?->value,
            'invalid_message' => t('Select an existing study.'),
            'constraints' => [new Assert\NotNull()],
        ]);

        $builder->add('hidden', CheckboxType::class, [
            'label' => t('Hide Member'),
            'required' => false,
        ]);

        $builder->add('submit', SubmitType::class, ['label' => t('Change Data')]);

        $builder->get('email')->addModelTransformer(new LowercaseTransformer());
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Member::class]);
    }
}
