<?php

declare(strict_types=1);

namespace Database\Service;

use Database\Mapper\{
    Member as MemberMapper,
};
use Database\Model\{
    Member as MemberModel,
};
use Report\Mapper\{
    Member as ReportMemberMapper,
};

class Api
{
    public function __construct(
        private readonly MemberMapper $memberMapper,
        private readonly ReportMemberMapper $reportMemberMapper,
    ) {
    }

    /**
     * Get active members.
     */
    public function getActiveMembers(): array
    {
        return array_map(function ($member) {
            return $member->toArrayApi();
        }, $this->getReportMemberMapper()->findActive());
    }

    /**
     * Get normal members.
     */
    public function getMembers(): array
    {
        return array_map(function ($member) {
            return $member->toArrayApi();
        }, $this->getReportMemberMapper()->findNormal());
    }

    /**
     * Get normal members.
     */
    public function getMember(int $id): ?array
    {
        return $this->getReportMemberMapper()->findSimple($id)?->toArrayApi();
    }

    /**
     * Get the member mapper.
     */
    private function getMemberMapper(): MemberMapper
    {
        return $this->memberMapper;
    }

    /**
     * Get the member mapper.
     */
    private function getReportMemberMapper(): ReportMemberMapper
    {
        return $this->reportMemberMapper;
    }
}
