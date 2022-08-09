<?php

namespace Database\Form;

use Database\Model\Member as MemberModel;
use Laminas\Form\Element\{
    Checkbox,
    Submit,
};
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;

class MemberLists extends Form implements InputFilterProviderInterface
{
    public function __construct(
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
                if ($lst->getName() == $list->getName()) {
                    $this->get('list-' . $list->getName())->setChecked(true);
                    break;
                }
            }
        }

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => 'Wijzig inschrijvingen',
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
