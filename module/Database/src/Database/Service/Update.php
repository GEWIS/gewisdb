<?php

namespace Database\Service;

use Application\Service\AbstractService;
use Database\Model\MemberUpdate;

class Update extends AbstractService
{

    public function storeMemberUpdateRequest($lidnr, $data)
    {
        $member = $this->getMemberMapper()->findSimple($lidnr);
        // Update everything which can be updated directly
        $member->applyUserUpdate($data);
        // Check for existing updates
        $update = $this->getUpdateMapper()->findMemberUpdate($lidnr);
        if ($update === null) {
            $update = new MemberUpdate();
        }
        if ($update->loadData($lidnr, $data)) {
            $this->getUpdateMapper()->persist($update);
        }
    }

    public function getPendingMemberUpdates()
    {
        return $this->getUpdateMapper()->getMemberUpdates();
    }

    public function approveMemberUpdate($lidnr)
    {
        $member = $this->getMemberMapper()->findSimple($lidnr);
        $update = $this->getUpdateMapper()->findMemberUpdate($lidnr);
        if ($member === null || $update === null) {
            return false;
        }
        $member->applyUpdate($update);
        $this->getUpdateMapper()->persist($member);
        $this->getUpdateMapper()->remove($update);
    }

    public function rejectMemberUpdate($lidnr)
    {
        $update = $this->getUpdateMapper()->findMemberUpdate($lidnr);
        if ($update === null) {
            return false;
        }
        $this->getUpdateMapper()->remove($update);
    }

    /**
     * Get the update mapper.
     *
     * @return \Database\Mapper\Update
     */
    public function getUpdateMapper()
    {
        return $this->getServiceManager()->get('database_mapper_update');
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
