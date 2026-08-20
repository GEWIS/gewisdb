<?php

declare(strict_types=1);

namespace App\Form\Database;

use App\Entity\Database\MailingList;
use App\Entity\Database\Member;
use App\Repository\Database\MailingListRepository;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_combine;
use function array_keys;
use function array_map;
use function array_unique;
use function array_values;
use function in_array;
use function is_array;
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
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $subscriptions = $this->subscriptionStates($options['member']);
        $locked = $this->lockedLists($subscriptions);
        $listNames = array_map(
            static fn (MailingList $list): string => $list->getName(),
            $this->mailingListRepository->findAll(),
        );

        $builder->add(
            'lists',
            ChoiceType::class,
            [
                'label' => t('Lists'),
                'expanded' => true,
                'multiple' => true,
                'required' => false,
                'choices' => array_combine(
                    $listNames,
                    $listNames,
                ),
                'data' => array_keys($subscriptions),
                // Resolved while the view is built rather than at build time, so the state follows the request locale.
                'choice_label' => function (string $name) use ($subscriptions): string {
                    if (!isset($subscriptions[$name])) {
                        return $name;
                    }

                    return $name . ' (' . $this->stateLabel(...$subscriptions[$name]) . ')';
                },
                'choice_attr' => static function (string $name) use ($locked): array {
                    if (
                        !in_array(
                            $name,
                            $locked,
                            true,
                        )
                    ) {
                        return [];
                    }

                    return ['disabled' => true];
                },
                'choice_translation_domain' => false,
            ],
        );

        // A disabled checkbox is not submitted at all, which arrives here as indistinguishable from one that was
        // unticked. Left alone, saving any change to this form reads every locked list as an unsubscribe and queues it
        // for removal; putting them back is what makes 'locked' mean unchanged.
        //
        // Ahead of ChoiceType's own PRE_SUBMIT, which sits at 0: that one rewrites the submitted list into a map from
        // child name to value, and a list of names appended after it no longer lines up with anything.
        $builder->get('lists')->addEventListener(
            FormEvents::PRE_SUBMIT,
            static function (PreSubmitEvent $event) use ($locked): void {
                $submitted = $event->getData();

                if (!is_array($submitted)) {
                    $submitted = [];
                }

                $event->setData(array_values(array_unique([...$submitted, ...$locked])));
            },
            priority: 1,
        );

        $builder->add(
            'submit',
            SubmitType::class,
            ['label' => t('Change Subscriptions')],
        );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null]);

        $resolver->setRequired('member');
        $resolver->setAllowedTypes(
            'member',
            Member::class,
        );
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

    /**
     * The lists whose subscription is waiting to be synced, and so cannot be changed until it has been.
     *
     * @param array<string, array{0: bool, 1: bool}> $subscriptions
     *
     * @return string[]
     */
    private function lockedLists(array $subscriptions): array
    {
        $locked = [];

        foreach ($subscriptions as $name => $state) {
            if ([false, false] === $state) {
                continue;
            }

            $locked[] = $name;
        }

        return $locked;
    }

    private function stateLabel(
        bool $toBeCreated,
        bool $toBeDeleted,
    ): string {
        if (
            $toBeCreated
            && $toBeDeleted
        ) {
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
