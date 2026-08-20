<?php

declare(strict_types=1);

namespace App\Form\Database;

use App\Entity\Database\Enums\OrganTypes;
use App\Form\Database\DataMapper\OrganRegulationMapper;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

use function array_filter;
use function array_values;
use function Symfony\Component\Translation\t;

class OrganRegulationType extends AbstractType
{
    public function __construct(private readonly OrganRegulationMapper $dataMapper)
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
        $regulated = array_values(array_filter(
            OrganTypes::cases(),
            static fn (OrganTypes $organType): bool => $organType->hasOrganRegulations(),
        ));

        $builder
            // Every organ type is offered, but the ones without organ regulations are shown disabled and refused on
            // submission, so that picking one is answered with an explanation rather than a missing option.
            ->add(
                'type',
                EnumType::class,
                [
                    'label' => t('Type'),
                    'class' => OrganTypes::class,
                    'choice_attr' => static fn (OrganTypes $organType) => $organType->hasOrganRegulations()
                        ? []
                        : ['disabled' => 'disabled'],
                    'expanded' => true,
                    'placeholder' => false,
                    'constraints' => [
                        new NotNull(),
                        new Choice(
                            choices: $regulated,
							// phpcs:ignore Generic.Files.LineLength.TooLong -- user-visible strings should not be split
                            message: 'Organ regulations can only be created for \'committee\', \'fraternity\', or \'financial audit committee\'.',
                        ),
                    ],
                ],
            )
            ->add(
                'abbr',
                TextType::class,
                [
                    'label' => t('Abbreviation'),
                    'constraints' => [
                        new NotBlank(),
                        new Length(
                            min: 2,
                            max: 128,
                        ),
                    ],
                ],
            )
            ->add(
                'date',
                DateType::class,
                [
                    'label' => t('Date of Body Regulation'),
                    'widget' => 'single_text',
                    'constraints' => [new NotNull()],
                ],
            )
            ->add(
                'author',
                MemberLookupType::class,
                ['label' => t('Author')],
            )
            ->add(
                'version',
                TextType::class,
                [
                    'label' => t('Version'),
                    'constraints' => [
                        new NotBlank(),
                        new Length(
                            min: 1,
                            max: 32,
                        ),
                    ],
                ],
            )
            ->add(
                'approve',
                ChoiceType::class,
                [
                    'label' => t('Approval'),
                    'choices' => [
                        '1',
                        '0',
                    ],
                    'choice_label' => static fn (string $choice) => '1' === $choice ? t('Approve') : t('Disapprove'),
                    'expanded' => true,
                    'placeholder' => false,
                    'required' => false,
                ],
            )
            ->add(
                'changes',
                ChoiceType::class,
                [
                    'label' => t('Modifications'),
                    'choices' => [
                        '1',
                        '0',
                    ],
                    'choice_label' => static fn (string $choice) => '1' === $choice
                        ? t('With Modifications')
                        : t('Without Modifications'),
                    'expanded' => true,
                    'placeholder' => false,
                    'required' => false,
                ],
            )
            ->add(
                'submit',
                SubmitType::class,
                ['label' => t('Add Body Regulation')],
            )
            ->setDataMapper($this->dataMapper);
    }

    #[Override]
    public function getParent(): string
    {
        return BaseDecisionType::class;
    }
}
