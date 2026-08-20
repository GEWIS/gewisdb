<?php

declare(strict_types=1);

namespace App\Form\Database;

use App\Entity\Database\Enums\MeetingTypes;
use App\Entity\Database\Meeting;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\LessThan;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

use function Symfony\Component\Translation\t;

/**
 * Creating the meeting decisions are then recorded against.
 *
 * Build this without form data: a meeting only takes shape once the form is submitted, and reading its fields off a
 * blank one would touch identifiers that are not set yet.
 */
class CreateMeetingType extends AbstractType
{
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
                EnumType::class,
                [
                    'label' => t('Meeting Type'),
                    'class' => MeetingTypes::class,
                    // The abbreviation the enum carries is not enough to pick a meeting type from, so this one form
                // spells the types out instead of letting the enum label itself.
                    'choice_label' => static fn (MeetingTypes $type) => match ($type) {
                        MeetingTypes::BV => t('BM (Board Meeting)'),
                        MeetingTypes::ALV => t('GMM (General Members Meeting)'),
                        MeetingTypes::VV => t('CM (Chair\'s Meeting)'),
                        MeetingTypes::VIRT => t('Virt (Virtual Meeting)'),
                    },
                    'placeholder' => false,
                    'constraints' => [new NotNull()],
                ],
            )
            ->add(
                'number',
                IntegerType::class,
                [
                    'label' => t('Meeting Number'),
                    'constraints' => [
                        new NotNull(),
                        new PositiveOrZero(),
                        new LessThan(100000),
                    ],
                ],
            )
            ->add(
                'date',
                DateType::class,
                [
                    'label' => t('Meeting Date'),
                    'widget' => 'single_text',
                    'constraints' => [new NotNull()],
                ],
            )
            ->add(
                'submit',
                SubmitType::class,
                ['label' => t('Add Meeting')],
            );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Meeting::class]);
    }
}
