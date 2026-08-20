<?php

declare(strict_types=1);

namespace App\Form\Database;

use App\Entity\Database\Enums\MembershipTypes;
use App\Entity\Database\Membership;
use DateTime;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

use function min;
use function Symfony\Component\Translation\t;

/**
 * Changes the membership type of a member. Without a membership the form is empty, which is how it is used while
 * approving a prospective member: there is no membership to change yet.
 */
class MembershipTypeType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $membership = $options['membership'];

        $typeOptions = [
            'label' => t('Membership Type'),
            'class' => MembershipTypes::class,
            'expanded' => true,
            // The radios say who each type applies to, which is more than the enum labels itself with.
            'choice_label' => static fn (MembershipTypes $type): TranslatableMessage => MembershipTypeChoices::label(
                $type,
            ),
            'invalid_message' => 'Select an existing membership type.',
            // Nothing is preselected while a prospective member is approved, and `Member::membership()` takes the type
            // as a non-nullable argument, so an unanswered form has to fail here rather than there.
            'constraints' => [new Assert\NotNull()],
        ];

        // The change date can only fall inside the membership it changes.
        $changeDateOptions = [
            'label' => t('Change Date'),
            'widget' => 'single_text',
            'constraints' => [new Assert\NotNull()],
        ];

        if (null !== $membership) {
            $typeOptions['data'] = $membership->getType();
            $changeDateOptions['data'] = min(
                $membership->getStartDate(),
                new DateTime(),
            );
            $changeDateOptions['attr'] = [
                'min' => $membership->getStartDate()->format('Y-m-d'),
                'max' => $membership->getEndDate()->format('Y-m-d'),
            ];
        }

        $builder->add(
            'type',
            EnumType::class,
            $typeOptions,
        );
        $builder->add(
            'changeDate',
            DateType::class,
            $changeDateOptions,
        );
        $builder->add(
            'submit',
            SubmitType::class,
            ['label' => t('Change Membership Type')],
        );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'membership' => null,
        ]);

        $resolver->setAllowedTypes(
            'membership',
            [
                Membership::class,
                'null',
            ],
        );
    }
}
