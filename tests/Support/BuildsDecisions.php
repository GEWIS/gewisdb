<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Entity\Database\Decision;
use App\Entity\Database\Enums\MeetingTypes;
use App\Entity\Database\Meeting;
use App\Entity\Database\SubDecision\Other;
use DateTime;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Builds the meeting → decision → subdecision chain the ledger is made of.
 *
 * The order in which the parts are put together is not free: each level copies the identity of the one above it when
 * it is attached, so a decision has to know its meeting before a subdecision joins it, and its point and number
 * before that. Every builder here attaches in that order.
 */
trait BuildsDecisions
{
    /**
     * @param int<0, max> $number
     */
    protected function meeting(
        MeetingTypes $type = MeetingTypes::VV,
        int $number = 3,
        string $date = '2026-08-20',
    ): Meeting {
        $meeting = new Meeting();
        $meeting->setType($type);
        $meeting->setNumber($number);
        $meeting->setDate(new DateTime($date));

        return $meeting;
    }

    protected function decision(
        ?Meeting $meeting = null,
        int $point = 5,
        int $number = 1,
    ): Decision {
        $decision = new Decision();
        // Also what gives the decision its collection of subdecisions, so nothing may be added before this.
        $decision->setMeeting($meeting ?? $this->meeting());
        $decision->setPoint($point);
        $decision->setNumber($number);

        return $decision;
    }

    /**
     * A subdecision carrying literal text, which is the one shape that does not go through a template.
     */
    protected function other(
        Decision $decision,
        string $content,
        int $sequence = 1,
    ): Other {
        $other = new Other();
        $other->setContent($content);
        $other->setSequence($sequence);
        $other->setDecision($decision);

        return $other;
    }

    /**
     * A translator that hands back the source string, so a test reads the template the entity asked for.
     */
    protected function translator(string $locale = 'nl'): TranslatorInterface
    {
        $translator = self::createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);
        $translator->method('getLocale')->willReturn($locale);

        return $translator;
    }
}
