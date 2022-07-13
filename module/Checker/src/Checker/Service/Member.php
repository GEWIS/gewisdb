<?php

namespace Checker\Service;

use Application\Service\AbstractService;
use Checker\Mapper\Member as MemberMapper;

class Member extends AbstractService
{
    /**
     * Fetch some members whose membership status should be checked.
     *
     * @return array Database\Model\Member
     */
    public function getMembersToCheck()
    {
        /** @var MemberMapper $memberMapper */
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
        /** @var MemberMapper $memberMapper */
        $memberMapper = $this->getServiceManager()->get('checker_mapper_member');

        return $memberMapper->getEndingMembershipsWithNormalTypes();
    }

    /**
     * Get members who require an adjustment to just their membership expiration.
     *
     * @return array
     */
    public function getExpiringMembershipsWithNormalTypes()
    {
        /** @var MemberMapper $memberMapper */
        $memberMapper = $this->getServiceManager()->get('checker_mapper_member');

        return $memberMapper->getExpiringMembershipsWithNormalTypes();
    }

    /**
     * @return MemberMapper
     */
    public function getMemberMapper()
    {
        return $this->getServiceManager()->get('checker_mapper_member');
    }
}
