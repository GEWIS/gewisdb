<?php

namespace Database\Service;

use Application\Service\AbstractService;

use Database\Model\Member as MemberModel;

class Member extends AbstractService
{

    /**
     * Subscribe a member.
     *
     * @param array $data
     *
     * @return Member member, null if failed.
     */
    public function subscribe($data)
    {
        $form = $this->getMemberForm();

        $form->bind(new MemberModel());

        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        // set some extra data
        $member = $form->getData();

        // generation is the current year
        $member->setGeneration((int) date('Y'));

        // by default, we only add ordinary members
        $member->setType(MemberModel::TYPE_ORDINARY);

        // changed on date
        $date = new \DateTime();
        $date->setTime(0, 0);
        $member->setChangedOn($date);

        $this->getEventManager()->trigger(__FUNCTION__ . '.pre', $this, array('member' => $member));
        $this->getMemberMapper()->persist($member);
        $this->getEventManager()->trigger(__FUNCTION__ . '.post', $this, array('member' => $member));

        return $member;
    }

    /**
     * Get member info.
     *
     * @param int $id
     *
     * @return MemberModel
     */
    public function getMember($id)
    {
        return $this->getMemberMapper()->find($id);
    }

    /**
     * Search for a member.
     *
     * @param string $query
     */
    public function search($query)
    {
        return $this->getMemberMapper()->search($query);
    }

    /**
     * Get the member edit form.
     *
     * @param int $lidnr
     *
     * @return array Array with \Database\Form\MemberEdit and MemberModel
     */
    public function getMemberEditForm($lidnr)
    {
        $form = $this->getServiceManager()->get('database_form_memberedit');
        $member = $this->getMember($lidnr);
        $form->bind($member);
        return array(
            'member' => $member,
            'form' => $form
        );
    }

    /**
     * Get the member form.
     *
     * @return \Database\Form\Member
     */
    public function getMemberForm()
    {
        return $this->getServiceManager()->get('database_form_member');
    }

    /**
     * Get the member mapper.
     *
     * @return \Database\Mapper\Member
     */
    public function getMemberMapper()
    {
        return $this->getServiceManager()->get('database_mapper_member');
    }
}
