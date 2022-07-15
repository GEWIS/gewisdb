<?php

namespace Database\Form;

use Database\Model\Member as MemberModel;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilter;
use Laminas\InputFilter\InputFilterProviderInterface;

class MemberLists extends Form implements InputFilterProviderInterface
{
    protected $lists;

    public function __construct(MemberModel $member, $lists)
    {
        parent::__construct();

        $this->lists = $lists;

        foreach ($this->lists as $list) {
            $this->add([
                'name' => 'list-' . $list->getName(),
                'type' => 'checkbox',
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
            'type' => 'submit',
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
