<?php

declare(strict_types=1);

namespace Checker\Service;

use Checker\Mapper\Member as  MemberMapper;
use Database\Model\Member as DatabaseMemberModel;

class Member
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function __construct(
        private readonly MemberMapper $memberMapper,
        private readonly array $config,
    ) {
    }

    /**
     * Fetch some members whose membership status should be checked.
     *
     * @return DatabaseMemberModel[]
     */
    public function getMembersToCheck(): array
    {
        $config = $this->config['checker']['membership_api'];

        return $this->memberMapper->getMembersToCheck($config['max_total_requests'] - $config['max_manual_requests']);
    }

    /**
     * Get members who may require an adjustment to their membership type (based on whether their membership has ended).
     *
     * @return DatabaseMemberModel[]
     */
    public function getEndingMembershipsWithNormalTypes(): array
    {
        return $this->memberMapper->getEndingMembershipsWithNormalTypes();
    }

    /**
     * Get members who require an adjustment to just their membership expiration.
     *
     * @return DatabaseMemberModel[]
     */
    public function getExpiringMembershipsWithNormalTypes(): array
    {
        return $this->memberMapper->getExpiringMembershipsWithNormalTypes();
    }

    /**
     * Get members who are hidden or whose membership has expired.
     *
     * @return DatabaseMemberModel[]
     */
    public function getExpiredOrHiddenMembersWithAuthenticationKey(): array
    {
        return $this->memberMapper->getExpiredOrHiddenMembersWithAuthenticationKey();
    }

    public function getMemberMapper(): MemberMapper
    {
        return $this->memberMapper;
    }
}
