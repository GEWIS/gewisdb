<?php

declare(strict_types=1);

namespace Checker\Service;

use Checker\Mapper\Member as MemberMapper;
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
