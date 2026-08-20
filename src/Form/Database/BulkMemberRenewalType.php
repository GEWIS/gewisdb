<?php

declare(strict_types=1);

namespace App\Form\Database;

use App\Entity\Database\Enums\MembershipTypes;
use App\Validator\Database\BulkMemberIds;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

use function preg_split;
use function Symfony\Component\Translation\t;
use function trim;

/**
 * Renews a batch of memberships at once, entered as a free-form list of membership numbers.
 */
class BulkMemberRenewalType extends AbstractType
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
            'memberIds',
            TextareaType::class,
            [
                'label' => t('Membership numbers'),
                'constraints' => [
                    new Assert\NotBlank(),
                    new BulkMemberIds(),
                ],
            ],
        );

        $builder->add(
            'membershipType',
            EnumType::class,
            [
                'label' => t('Membership Type'),
                'class' => MembershipTypes::class,
                'expanded' => true,
                // The radios say who each type applies to, which is more than the enum labels itself with.
                'choice_label' => static function (MembershipTypes $type): TranslatableMessage {
                    return MembershipTypeChoices::label($type);
                },
                'invalid_message' => 'Select an existing membership type.',
                // Without this an unanswered form validates, and the renewal then previews and applies nothing
                // at all, with no indication of why.
                'constraints' => [new Assert\NotNull()],
            ],
        );

        $builder->add(
            'submit',
            SubmitType::class,
            ['label' => t('Preview changes')],
        );

        // The second step of the flow submits through this button instead, which is how the preview is confirmed.
        $builder->add(
            'intent',
            SubmitType::class,
            ['label' => t('Confirm changes')],
        );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null]);
    }

    /**
     * The submitted membership numbers, which {@see BulkMemberIds} has already established to be a list of unique
     * numeric IDs.
     *
     * @return int[]
     */
    public static function parseMemberIds(string $memberIds): array
    {
        $tokens = preg_split(
            '/[\s,;]+/',
            trim($memberIds),
        ) ?: [];
        $parsed = [];

        foreach ($tokens as $token) {
            if ('' === $token) {
                continue;
            }

            $parsed[] = (int) $token;
        }

        return $parsed;
    }
}
