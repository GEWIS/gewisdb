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
