<?php

declare(strict_types=1);

namespace Database\Service;

use Report\Mapper\{
    Member as ReportMemberMapper,
};

class Api
{
    public function __construct(private readonly ReportMemberMapper $reportMemberMapper)
    {
    }

    /**
     * Get active members.
     */
    public function getActiveMembers(): array
    {
        return array_map(
            function ($member) {
                return $member->toArrayApi(true);
            },
            $this->getReportMemberMapper()->findActive(),
        );
    }

    /**
     * Get normal members.
     */
    public function getMembers(): array
    {
        return array_map(
            function ($member) {
                return $member->toArrayApi();
            },
            $this->getReportMemberMapper()->findNormal(),
        );
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
    private function getReportMemberMapper(): ReportMemberMapper
    {
        return $this->reportMemberMapper;
    }
}
