<?php

declare(strict_types=1);

namespace App\Form\Database;

use App\Entity\Database\MailingList;
use App\Entity\Database\Member;
use App\Repository\Database\MailingListRepository;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_combine;
use function array_keys;
use function array_map;
use function Symfony\Component\Translation\t;

/**
 * The mailing lists a member is subscribed to. Subscriptions that are still waiting to be synced cannot be changed
 * until that has happened, so they are shown but locked.
 */
class MemberListsType extends AbstractType
{
    public function __construct(
        private readonly MailingListRepository $mailingListRepository,
        private readonly TranslatorInterface $translator,
    ) {
    }

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
        $subscriptions = $this->subscriptionStates($options['member']);
        $listNames = array_map(
            static fn (MailingList $list): string => $list->getName(),
            $this->mailingListRepository->findAll(),
        );

        $builder->add('lists', ChoiceType::class, [
            'label' => t('Lists'),
            'expanded' => true,
            'multiple' => true,
            'required' => false,
            'choices' => array_combine($listNames, $listNames),
            'data' => array_keys($subscriptions),
            // Resolved while the view is built rather than at build time, so the state follows the request locale.
            'choice_label' => function (string $name) use ($subscriptions): string {
                if (!isset($subscriptions[$name])) {
                    return $name;
                }

                return $name . ' (' . $this->stateLabel(...$subscriptions[$name]) . ')';
            },
            'choice_attr' => static function (string $name) use ($subscriptions): array {
                if (
                    !isset($subscriptions[$name])
                    || [false, false] === $subscriptions[$name]
                ) {
                    return [];
                }

                return ['disabled' => true];
            },
            'choice_translation_domain' => false,
        ]);

        $builder->add('submit', SubmitType::class, ['label' => t('Change Subscriptions')]);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null]);

        $resolver->setRequired('member');
        $resolver->setAllowedTypes('member', Member::class);
    }

    /**
     * The pending sync state per list the member is subscribed to, as `[toBeCreated, toBeDeleted]`.
     *
     * @return array<string, array{0: bool, 1: bool}>
     */
    private function subscriptionStates(Member $member): array
    {
        $states = [];

        foreach ($member->getMailingListMemberships() as $subscription) {
            $name = $subscription->getMailingList()->getName();

            $states[$name] = [
                ($states[$name][0] ?? false) || $subscription->isToBeCreated(),
                ($states[$name][1] ?? false) || $subscription->isToBeDeleted(),
            ];
        }

        return $states;
    }

    private function stateLabel(
        bool $toBeCreated,
        bool $toBeDeleted,
    ): string {
        if ($toBeCreated && $toBeDeleted) {
            return $this->translator->trans('email address change pending');
        }

        if ($toBeDeleted) {
            return $this->translator->trans('to be deleted');
        }

        if ($toBeCreated) {
            return $this->translator->trans('to be created');
        }

        return $this->translator->trans('synced');
    }
}
