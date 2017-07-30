<?php

namespace Database\Service;

use Application\Service\AbstractService;
use Database\Model\MemberUpdate;

class Update extends AbstractService
{

    public function storeUpdateRequest($lidnr, $data)
    {
        $member = $this->getMemberMapper()->findSimple($lidnr);
        // Update everything which can be updated directly
        $member->applyUserUpdate($data);
        // Check for existing updates
        $update = $this->getUpdateMapper()->findMemberUpdate($lidnr);
        if ($update === null) {
            $update = new MemberUpdate();
        }
        $update->loadData($lidnr, $data);

        $this->getUpdateMapper()->persist($update);
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
