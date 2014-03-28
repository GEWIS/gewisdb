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

        // expiration date is in 5 years.
        $date = new \DateTime();
        $date->add(new \DateInterval('P5Y'));
        $date->setTime(0, 0);
        $member->setExpiration($date);

        return null;
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
