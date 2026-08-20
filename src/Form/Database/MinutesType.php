<?php

declare(strict_types=1);

namespace App\Form\Database;

use App\Form\Database\DataMapper\MinutesMapper;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

use function Symfony\Component\Translation\t;

class MinutesType extends AbstractType
{
    public function __construct(private readonly MinutesMapper $dataMapper)
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
            // Only the source for the meeting autocomplete, which fills in the meeting reference below.
            ->add(
                'name',
                TextType::class,
                [
                    'label' => t('Meeting'),
                    'mapped' => false,
                    'required' => false,
                ],
            )
            ->add(
                'author',
                MemberLookupType::class,
                ['label' => t('Author')],
            )
            ->add(
                'submit',
                SubmitType::class,
                ['label' => t('Submit')],
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
            // The meeting the minutes are of, which is not the meeting the decision is taken in.
            ->add(
                'fmeeting',
                MeetingType::class,
            )
            ->setDataMapper($this->dataMapper);
    }

    #[Override]
    public function getParent(): string
    {
        return BaseDecisionType::class;
    }
}
