<?php

declare(strict_types=1);

namespace App\Tests\Entity\Database;

use App\Entity\Application\Enums\AppLanguages;
use App\Entity\Database\Decision;
use App\Entity\Database\Enums\MeetingTypes;
use App\Tests\Support\BuildsDecisions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Decision::class)]
class DecisionTest extends TestCase
{
    use BuildsDecisions;

    /**
     * The hash is how a decision is referred to everywhere, including from the decisions that amend it, so its shape
     * is part of the record rather than a display detail.
     */
    public function testIdentifiesItselfByItsMeetingPointAndNumber(): void
    {
        $decision = $this->decision(
            $this->meeting(MeetingTypes::ALV, 42),
            5,
            1,
        );

        self::assertSame('ALV 42.5.1', $decision->getHash());
    }

    public function testTakesHalfOfThatIdentityFromTheMeetingItWasMadeIn(): void
    {
        $meeting = $this->meeting(MeetingTypes::BV, 1337);
        $decision = $this->decision($meeting);

        self::assertSame(MeetingTypes::BV, $decision->getMeetingType());
        self::assertSame(1337, $decision->getMeetingNumber());
        self::assertSame($meeting, $decision->getMeeting());
        self::assertTrue($meeting->getDecisions()->contains($decision));
    }

    public function testReadsAsItsSubdecisionsInSequence(): void
    {
        $decision = $this->decision();
        $this->other($decision, 'Er wordt een taart gekocht.', 1);
        $this->other($decision, 'De taart is van appel.', 2);

        self::assertSame(
            'Er wordt een taart gekocht. De taart is van appel.',
            $decision->getContent($this->translator()),
        );
    }

    /**
     * Attaching goes both ways, and doing both is how the content used to end up printed twice.
     */
    public function testDoesNotCountASubdecisionTwiceWhenItIsAlsoAddedByHand(): void
    {
        $decision = $this->decision();
        $subdecision = $this->other($decision, 'Er wordt een taart gekocht.');

        $decision->addSubdecision($subdecision);
        $decision->addSubdecisions([$subdecision]);

        self::assertCount(1, $decision->getSubdecisions());
        self::assertSame(
            'Er wordt een taart gekocht.',
            $decision->getContent($this->translator()),
        );
    }

    /**
     * The statutory content is the Dutch text; asking for another language asks each subdecision for its own.
     */
    public function testAsksEachSubdecisionForTheLanguageThatWasRequested(): void
    {
        $decision = $this->decision();
        $this->other($decision, 'Er wordt een taart gekocht.');

        self::assertSame(
            'Er wordt een taart gekocht.',
            $decision->getTranslatedContent($this->translator(), AppLanguages::Dutch),
        );
        self::assertSame(
            'If you are reading this, the secretary has not done their job.',
            $decision->getTranslatedContent($this->translator(), AppLanguages::English),
        );
    }

    /**
     * The minutes are typeset from these strings, and a secretary's free text reaches them unaltered.
     */
    #[DataProvider('charactersLaTeXWouldTakeAsItsOwn')]
    public function testEscapesForLaTeXOnlyWhenAsked(
        string $content,
        string $escaped,
    ): void {
        $decision = $this->decision();
        $this->other($decision, $content);

        self::assertSame($content, $decision->getContent($this->translator()));
        self::assertSame(
            $escaped,
            $decision->getContent($this->translator(), true),
        );
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function charactersLaTeXWouldTakeAsItsOwn(): array
    {
        return [
            'an ampersand' => ['Jan & Piet', 'Jan \& Piet'],
            'a percent sign' => ['50% korting', '50\% korting'],
            'an amount' => ['$5 & €5', '\$5 \& €5'],
            'braces' => ['{de taart}', '\{de taart\}'],
            'an underscore' => ['de_taart', 'de\_taart'],
            // The replacements for these three introduce braces of their own, which a second pass would escape again.
            'a backslash' => ['de\taart', 'de\textbackslash{}taart'],
            'a tilde' => ['~taart', '\textasciitilde{}taart'],
            'a caret' => ['^taart', '\textasciicircum{}taart'],
            'all of it at once' => ['{~\}', '\{\textasciitilde{}\textbackslash{}\}'],
        ];
    }

    public function testIsNotAnnulledUntilAnAnnulmentSaysSo(): void
    {
        $decision = $this->decision();

        self::assertFalse($decision->isAnnulled());
        self::assertNull($decision->getAnnulledBy());
    }
}
