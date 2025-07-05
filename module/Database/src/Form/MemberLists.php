<?php

declare(strict_types=1);

namespace Database\Form;

use Database\Model\MailingList as MailingListModel;
use Database\Model\MailingListMember as MailingListMemberModel;
use Database\Model\Member as MemberModel;
use Laminas\Form\Element\MultiCheckbox;
use Laminas\Form\Element\Submit;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;

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

        $memberLists = $member->getMailingListMemberships();

        $listOptions = [];
        foreach ($this->lists as $list) {
            $listName = $list->getName();

            $selected = $memberLists->exists(
                static function ($key, MailingListMemberModel $mlm) use ($listName) {
                    return $listName === $mlm->getMailingList()->getName();
                },
            );

            $label = $listName;
            if ($selected) {
                $toBeCreated = $memberLists->exists(
                    static function ($key, MailingListMemberModel $mlm) use ($listName) {
                        return $mlm->isToBeCreated() && $listName === $mlm->getMailingList()->getName();
                    },
                );
                $toBeDeleted = $memberLists->exists(
                    static function ($key, MailingListMemberModel $mlm) use ($listName) {
                        return $mlm->isToBeDeleted() && $listName === $mlm->getMailingList()->getName();
                    },
                );
                $disabled = $toBeDeleted || $toBeCreated;

                $label .= ' (';

                if ($toBeCreated && $toBeDeleted) {
                    $label .= $this->translator->translate('email address change pending');
                } elseif ($toBeDeleted) {
                    $label .= $this->translator->translate('to be deleted');
                } elseif ($toBeCreated) {
                    $label .= $this->translator->translate('to be created');
                } else {
                    $label .= $this->translator->translate('synced');
                }

                $label .= ')';
            } else {
                $disabled = false;
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
     * Should use Explode validator by default, so we only need to change required state
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'lists' => [
                'required' => false,
            ],
        ];
    }
}
