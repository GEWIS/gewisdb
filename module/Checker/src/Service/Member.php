<?php

namespace Checker\Service;

use Checker\Mapper\Member as MemberMapper;

class Member
{
    /** @var MemberMapper $memberMapper */
    private $memberMapper;

    /** @var array $config */
    private $config;

    /**
     * @param MemberMapper $memberMapper
     * @param array $config
     */
    public function __construct(
        MemberMapper $memberMapper,
        array $config,
    ) {
        $this->memberMapper = $memberMapper;
        $this->config = $config;
    }

    /**
     * Fetch some members whose membership status should be checked.
     *
     * @return array
     */
    public function getMembersToCheck(): array
    {
        $config = $this->config['checker']['membership_api'];

        return $this->memberMapper->getMembersToCheck($config['max_total_requests'] - $config['max_manual_requests']);
    }

    /**
     * Get members who may require an adjustment to their membership type (based on whether their membership has ended).
     *
     * @return array
     */
    public function getEndingMembershipsWithNormalTypes(): array
    {
        return $this->memberMapper->getEndingMembershipsWithNormalTypes();
    }

    /**
     * Get members who require an adjustment to just their membership expiration.
     *
     * @return array
     */
    public function getExpiringMembershipsWithNormalTypes(): array
    {
        return $this->memberMapper->getExpiringMembershipsWithNormalTypes();
    }

    /**
     * @return MemberMapper
     */
    public function getMemberMapper(): MemberMapper
    {
        return $this->memberMapper;
    }
}
