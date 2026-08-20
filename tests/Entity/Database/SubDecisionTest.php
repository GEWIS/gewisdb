<?php

declare(strict_types=1);

namespace App\Tests\Entity\Database;

use App\Entity\Database\Enums\MeetingTypes;
use App\Entity\Database\SubDecision;
use App\Tests\Support\BuildsDecisions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SubDecision::class)]
class SubDecisionTest extends TestCase
{
    use BuildsDecisions;

    /**
     * The sibling of the decision's hash one level down. The sequence is part of it, and part of the primary key,
     * which is why subdecisions can be added to a decision but never reordered within it.
     */
    public function testIdentifiesItselfByItsDecisionAndSequence(): void
    {
        $decision = $this->decision(
            $this->meeting(MeetingTypes::ALV, 42),
            5,
            1,
        );
        $subdecision = $this->other($decision, 'Er wordt een taart gekocht.', 2);

        self::assertSame('ALV 42.5.1.2', $subdecision->getHash());
        self::assertSame($decision->getHash() . '.2', $subdecision->getHash());
    }

    public function testCopiesTheIdentityOfTheDecisionItJoins(): void
    {
        $decision = $this->decision(
            $this->meeting(MeetingTypes::VIRT, 7),
            2,
            3,
        );
        $subdecision = $this->other($decision, 'Er wordt een taart gekocht.');

        self::assertSame(MeetingTypes::VIRT, $subdecision->getMeetingType());
        self::assertSame(7, $subdecision->getMeetingNumber());
        self::assertSame(2, $subdecision->getDecisionPoint());
        self::assertSame(3, $subdecision->getDecisionNumber());
        self::assertSame($decision, $subdecision->getDecision());
        self::assertTrue($decision->getSubdecisions()->contains($subdecision));
    }

    /**
     * Rendering without naming a language takes the translator's, which is the visitor's.
     */
    public function testFallsBackOnTheTranslatorsLocaleWhenNoLanguageIsNamed(): void
    {
        $subdecision = $this->other($this->decision(), 'Er wordt een taart gekocht.');

        self::assertSame(
            'Er wordt een taart gekocht.',
            $subdecision->getContent($this->translator('nl')),
        );
        self::assertSame(
            'If you are reading this, the secretary has not done their job.',
            $subdecision->getContent($this->translator('en')),
        );
    }
}
