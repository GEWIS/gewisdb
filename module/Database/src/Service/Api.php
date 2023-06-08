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
     * Get active members and inactive fraternity members.
     *
     * It is good to note here that the includeInactiveFraternity argument
     * only changes who is returned. If someone is active in another organ,
     * their inactive fraternity membership still gets returned as organ membership
     *
     * @param array<array-key,string> $additionalProperties
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    public function getActiveMembers(
        array $additionalProperties,
        bool $includeInactiveFraternity = false,
    ): array {
        return array_map(
            static function ($member) use ($additionalProperties) {
                return $member->toArrayApi($additionalProperties);
            },
            $this->getReportMemberMapper()->findActive($includeInactiveFraternity),
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
