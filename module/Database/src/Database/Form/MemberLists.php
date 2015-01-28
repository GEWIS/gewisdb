<?php

namespace Database\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterProviderInterface;

use Database\Model\Member as MemberModel;

class MemberLists extends Form
    implements InputFilterProviderInterface
{

    protected $lists;

    public function __construct(MemberModel $member, $lists)
    {
        parent::__construct();

        $this->lists = $lists;

        foreach ($this->lists as $list) {
            $this->add(array(
                'name' => 'list-' . $list->getName(),
                'type' => 'checkbox',
                'options' => array(
                    'label' => '<strong>' . $list->getName() . '</strong> ' . $list->getDescription()
                )
            ));
            foreach ($member->getLists() as $lst) {
                if ($lst->getName() == $list->getName()) {
                    $this->get('list-' . $list->getName())->setChecked(true);
                    break;
                }
            }
        }

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => 'Wijzig inschrijvingen'
            )
        ));
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification()
    {
        return array();
    }
}
