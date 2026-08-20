<?php

declare(strict_types=1);

namespace App\Tests\Entity\Database\SubDecision;

use App\Entity\Application\Enums\AppLanguages;
use App\Entity\Database\Decision;
use App\Entity\Database\Enums\MeetingTypes;
use App\Entity\Database\Meeting;
use App\Entity\Database\SubDecision\Annulment;
use App\Tests\Support\BuildsDecisions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Annulment::class)]
class AnnulmentTest extends TestCase
{
    use BuildsDecisions;

    /**
     * A decision is never edited, only annulled by a later one, so the annulment has to carry what it took back: the
     * reference to the decision and the text that decision had.
     */
    public function testQuotesTheDecisionItTakesBack(): void
    {
        $target = $this->decision(
            $this->meeting(MeetingTypes::ALV, 42),
            5,
            1,
        );
        $this->other($target, 'Er wordt een taart gekocht.');

        $annulment = $this->annulmentOf($target);

        self::assertSame(
            'Besluit ALV 42.5.1 wordt nietig verklaard. Het besluit luidde: "Er wordt een taart gekocht."',
            $annulment->getTranslatedContent($this->translator(), AppLanguages::Dutch),
        );
    }

    /**
     * The reference is the hash and only the hash: there is no alternative form of it to translate into.
     */
    public function testKeepsTheReferenceUntranslatedWhileTranslatingTheRest(): void
    {
        $target = $this->decision(
            $this->meeting(MeetingTypes::ALV, 42),
            5,
            1,
        );
        $this->other($target, 'Er wordt een taart gekocht.');

        $content = $this->annulmentOf($target)
            ->getTranslatedContent($this->translator(), AppLanguages::English);

        self::assertStringContainsString('ALV 42.5.1', $content);
        self::assertStringContainsString(
            'If you are reading this, the secretary has not done their job.',
            $content,
        );
        self::assertStringNotContainsString('Er wordt een taart gekocht.', $content);
    }

    public function testIsItselfASubdecisionOfTheMeetingThatAnnulled(): void
    {
        $target = $this->decision($this->meeting(MeetingTypes::ALV, 42));
        $annulment = $this->annulmentOf($target, $this->meeting(MeetingTypes::ALV, 43));

        self::assertSame('ALV 43.5.1.1', $annulment->getHash());
        self::assertSame($target, $annulment->getTarget());
    }

    private function annulmentOf(
        Decision $target,
        ?Meeting $meeting = null,
    ): Annulment {
        $annulment = new Annulment();
        $annulment->setSequence(1);
        $annulment->setDecision($this->decision($meeting ?? $this->meeting(MeetingTypes::ALV, 43)));
        $annulment->setTarget($target);

        return $annulment;
    }
}
