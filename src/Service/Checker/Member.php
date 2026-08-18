<?php

declare(strict_types=1);

namespace App\Service\Checker;

use App\Entity\Database\Member as MemberModel;
use App\Repository\Checker\MemberRepository;

class Member
{
    public function __construct(private readonly MemberRepository $memberRepository)
    {
    }

    /**
     * Get members who are hidden or whose membership has expired.
     *
     * @return MemberModel[]
     */
    public function getExpiredOrHiddenMembersWithAuthenticationKey(): array
    {
        return $this->memberRepository->getExpiredOrHiddenMembersWithAuthenticationKey();
    }

    public function persist(MemberModel $member): void
    {
        $this->memberRepository->persist($member);
    }
}
