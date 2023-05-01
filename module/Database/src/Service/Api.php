<?php

declare(strict_types=1);

namespace Database\Service;

use Report\Mapper\Member as ReportMemberMapper;

use function array_map;

class Api
{
    public function __construct(private readonly ReportMemberMapper $reportMemberMapper)
    {
    }

    /**
     * Get active members.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    public function getActiveMembers(bool $includeOrganMembership): array
    {
        return array_map(
            static function ($member) use ($includeOrganMembership) {
                return $member->toArrayApi($includeOrganMembership);
            },
            $this->getReportMemberMapper()->findActive(),
        );
    }

    /**
     * Get normal members.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    public function getMembers(): array
    {
        return array_map(
            static function ($member) {
                return $member->toArrayApi();
            },
            $this->getReportMemberMapper()->findNormal(),
        );
    }

    /**
     * Get normal members.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
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
