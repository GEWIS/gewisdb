<?php

namespace Checker\Service;

use Application\Service\AbstractService;

class Member extends AbstractService
{
    /**
     * Fetch some members whose membership status should be checked.
     *
     * @return array Database\Model\Member
     */
    public function getMembersToCheck()
    {
        $memberMapper = $this->getServiceManager()->get('checker_mapper_member');
        $config = $this->getServiceManager()->get('config')['checker']['membership_api'];

        return $memberMapper->getMembersToCheck($config['max_total_requests'] - $config['max_manual_requests']);
    }

    /**
     * Get members who may require an adjustment to their membership type (based on whether their membership has ended).
     *
     * @return array
     */
    public function getEndingMembershipsWithNormalTypes()
    {
        $memberMapper = $this->getServiceManager()->get('checker_mapper_member');

        return $memberMapper->getEndingMembershipsWithNormalTypes();
    }

    /**
     * @return \Checker\Mapper\Member
     */
    public function getMemberMapper()
    {
        return $this->getServiceManager()->get('checker_mapper_member');
    }
}
