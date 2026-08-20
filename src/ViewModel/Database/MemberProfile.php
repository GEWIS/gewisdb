<?php

declare(strict_types=1);

namespace App\ViewModel\Database;

use App\Entity\Database\AuditEntry;
use App\Entity\Database\Enums\MembershipTypes;
use App\Entity\Database\Member;
use App\Entity\Database\SubDecision\Installation;
use DateTime;
use DateTimeInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_map;
use function in_array;

/**
 * The member page: the member themselves, plus everything about them that the page cannot read off the entity.
 *
 * The two flags decide whether the page offers the actions next to the membership; both are questions about what is
 * still possible for this member, which is why they are answered once here instead of in the markup.
 */
final readonly class MemberProfile
{
    /**
     * @param OrganRow[]      $organs
     * @param AuditEntryRow[] $notes
     */
    private function __construct(
        public Member $member,
        public bool $hasCorrectInstallations,
        public ?DateTimeInterface $membershipEndsOn,
        public bool $canChangeMembershipType,
        public bool $canExtend,
        public array $organs,
        public array $notes,
    ) {
    }

    public static function fromMember(
        Member $member,
        bool $hasCorrectInstallations,
        TranslatorInterface $translator,
        UrlGeneratorInterface $urlGenerator,
    ): self {
        // The last membership rather than the current one: a membership that has been renewed already is what the
        // next change would apply to.
        $lastMembership = $member->getLastMembership();

        return new self(
            $member,
            $hasCorrectInstallations,
            $member->getMembershipEndDate(),
            null !== $lastMembership
                && MembershipTypes::Honorary !== $lastMembership->getType(),
            // Only the types that have to be renewed by hand can be extended, and only once less than a year of the
            // membership is left: the extension always lands on the first of July after the current expiration.
            null !== $lastMembership
                && $member->getExpiration() < new DateTime('+1 year')
                && in_array(
                    $lastMembership->getType(),
                    [
                        MembershipTypes::External,
                        MembershipTypes::Graduate,
                    ],
                    true,
                ),
            // Without the installations that hold up, the ones on the member are whatever happens to be on file,
            // discharges and annulments included, so they are not stated at all.
            $hasCorrectInstallations
                ? array_map(
                    static fn (Installation $installation): OrganRow => OrganRow::fromInstallation(
                        $installation,
                        $urlGenerator,
                    ),
                    $member->getInstallations()->toArray(),
                )
                : [],
            array_map(
                static fn (AuditEntry $entry): AuditEntryRow => AuditEntryRow::fromEntry(
                    $entry,
                    $translator,
                ),
                $member->getAuditEntries()->toArray(),
            ),
        );
    }
}
