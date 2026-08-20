<?php

declare(strict_types=1);

namespace App\Form\Database;

use App\Form\Database\DataMapper\BudgetMapper;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

use function Symfony\Component\Translation\t;

class BudgetType extends AbstractType
{
    public function __construct(private readonly BudgetMapper $dataMapper)
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
                'type',
                ChoiceType::class,
                [
                    'label' => t('Budget/Statement'),
                    'choices' => [
                        'budget',
                        'statement',
                    ],
                    'choice_label' => static fn (string $choice) => 'budget' === $choice ? t('Budget') : t('Statement'),
                    'placeholder' => false,
                    'constraints' => [new NotBlank()],
                ],
            )
            ->add(
                'name',
                TextType::class,
                [
                    'label' => t('Name'),
                    'constraints' => [
                        new NotBlank(),
                        new Length(
                            min: 3,
                            max: 255,
                        ),
                    ],
                ],
            )
            ->add(
                'date',
                DateType::class,
                [
                    'label' => t('Date of Budget/Statement'),
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
            // Approval and modifications carry no constraints: anything that is not an explicit yes counts as a no,
            // which is what the original form falls back to as well.
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
                ['label' => t('Add Budget/Statement')],
            )
            ->setDataMapper($this->dataMapper);
    }

    #[Override]
    public function getParent(): string
    {
        return BaseDecisionType::class;
    }
}
