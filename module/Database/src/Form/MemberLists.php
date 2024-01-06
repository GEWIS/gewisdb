<?php

declare(strict_types=1);

namespace Database\Form;

use Database\Model\MailingList as MailingListModel;
use Database\Model\Member as MemberModel;
use Laminas\Form\Element\Checkbox;
use Laminas\Form\Element\Submit;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;

/**
 * @template TFilteredValues
 *
 * @extends Form<TFilteredValues>
 */
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

        foreach ($this->lists as $list) {
            $this->add([
                'name' => 'list-' . $list->getName(),
                'type' => Checkbox::class,
                'options' => [
                    'label' => '<strong>' . $list->getName() . '</strong> ' . $list->getDescription(),
                ],
            ]);
            foreach ($member->getLists() as $lst) {
                if ($lst->getName() === $list->getName()) {
                    $this->get('list-' . $list->getName())->setChecked(true);
                    break;
                }
            }
        }

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
