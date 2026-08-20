<?php

declare(strict_types=1);

namespace App\ViewModel\Database;

use App\Entity\Database\Member;
use DateTimeInterface;

/**
 * One member as a search answers for them.
 *
 * The overview and the lookups on the decision forms both read this, so it carries what a result row shows and the
 * address of the member's page -- and nothing else: the member's authentication key has no business in a response
 * that only has to identify them.
 */
final readonly class MemberSearchResult
{
    private function __construct(
        public int $lidnr,
        public string $fullName,
        public ?string $email,
        public int $generation,
        public string $expiration,
        public bool $deleted,
        public string $url,
    ) {
    }

    public static function fromMember(
        Member $member,
        string $url,
    ): self {
        return new self(
            $member->getLidnr(),
            $member->getFullName(),
            $member->getEmail(),
            $member->getGeneration(),
            $member->getExpiration()->format(DateTimeInterface::ATOM),
            $member->getDeleted(),
            $url,
        );
    }
}
