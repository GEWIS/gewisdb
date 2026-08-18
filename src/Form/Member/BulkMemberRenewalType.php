<?php

declare(strict_types=1);

namespace App\Form\Member;

use App\Entity\Member\Enums\MembershipTypes;
use App\Form\DataTransformer\StringToEnumTransformer;
use App\Validator\Member\BulkMemberIds;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

use function array_column;
use function array_combine;
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

    /**
     * @param array<string, mixed> $options
     */
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder->add('memberIds', TextareaType::class, [
            'label' => t('Membership numbers'),
            'constraints' => [
                new Assert\NotBlank(),
                new BulkMemberIds(),
            ],
        ]);

        $membershipTypes = array_column(MembershipTypes::cases(), 'value');

        $builder->add('membershipType', ChoiceType::class, [
            'label' => t('Membership Type'),
            'expanded' => true,
            'choices' => array_combine($membershipTypes, $membershipTypes),
            'choice_label' => static fn (string $type): TranslatableMessage => MembershipTypeChoices::label(
                MembershipTypes::from($type),
            ),
            'invalid_message' => t('Select an existing membership type.'),
        ]);

        $builder->add('submit', SubmitType::class, ['label' => t('Preview changes')]);

        // The second step of the flow submits through this button instead, which is how the preview is confirmed.
        $builder->add('intent', SubmitType::class, ['label' => t('Confirm changes')]);

        $builder->get('membershipType')->addModelTransformer(new StringToEnumTransformer(MembershipTypes::class));
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
        $tokens = preg_split('/[\s,;]+/', trim($memberIds)) ?: [];
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
