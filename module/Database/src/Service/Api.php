<?php

declare(strict_types=1);

namespace Database\Service;

use Database\Mapper\{
    Member as MemberMapper,
};
use Database\Model\{
    Member as MemberModel,
};

class Api
{
    public function __construct(
        private readonly MemberMapper $memberMapper,
    ) {
    }

    /**
     * Get normal members.
     */
    public function getMembers(): array
    {
        return array_map(function ($member) {
            return $member->toArrayApi();
        }, $this->getMemberMapper()->findNormal());
    }

    /**
     * Get normal members.
     */
    public function getMember(int $id): array
    {
        return $this->getMemberMapper()->findSimple($id)->toArrayApi();
    }

    /**
     * Get the member mapper.
     */
    private function getMemberMapper(): MemberMapper
    {
        return $this->memberMapper;
    }
}
