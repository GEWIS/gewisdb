<?php

declare(strict_types=1);

namespace Database\Form;

use Database\Model\MailingList as MailingListModel;
use Database\Model\Member as MemberModel;
use Laminas\Form\Element\MultiCheckbox;
use Laminas\Form\Element\Submit;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;

use function array_key_exists;

class MemberLists extends Form implements InputFilterProviderInterface
{
    /**
     * @param MailingListModel[] $lists
     */
    public function __construct(
        private readonly Translator $translator,
        MemberModel $member,
        protected readonly array $lists,
    ) {
        parent::__construct();

        $memberLists = [];
        foreach ($member->getMailingListMemberships() as $mailingListMember) {
            $memberLists[$mailingListMember->getMailingList()->getName()] = [
                'synced' => null !== $mailingListMember->getLastSyncOn() && $mailingListMember->isLastSyncSuccess(),
                'toBeDeleted' => $mailingListMember->isToBeDeleted(),
            ];
        }

        $listOptions = [];
        foreach ($this->lists as $list) {
            $listName = $list->getName();

            $selected = array_key_exists($listName, $memberLists);
            $synced = $memberLists[$listName]['synced'];
            $toBeDeleted = $memberLists[$listName]['toBeDeleted'];
            $disabled = $selected && ($toBeDeleted || !$synced);

            $label = $listName;
            if ($selected) {
                $label .= ' (';

                if (
                    $synced
                    && $toBeDeleted
                ) {
                    $label .= $this->translator->translate('to be deleted');
                } elseif (
                    $synced
                    && !$toBeDeleted
                ) {
                    $label .= $this->translator->translate('synced');
                } elseif (
                    !$synced
                    && $toBeDeleted
                ) {
                    $label .= $this->translator->translate('to be deleted');
                } else {
                    $label .= $this->translator->translate('to be synced');
                }

                $label .= ')';
            }

            $listOptions[] = [
                'value' => $listName,
                'label' => $label,
                'selected' => $selected,
                'disabled' => $disabled,
            ];
        }

        $this->add([
            'type' => MultiCheckbox::class,
            'name' => 'lists',
            'options' => [
                'label' => $this->translator->translate('Lists'),
                'value_options' => $listOptions,
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => $this->translator->translate('Change Subscriptions'),
            ],
        ]);
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification(): array
    {
        return [];
    }
}
