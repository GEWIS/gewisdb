<?php

declare(strict_types=1);

namespace App\Tests\Entity\Database\SubDecision;

use App\Entity\Application\Enums\AppLanguages;
use App\Entity\Database\Decision;
use App\Entity\Database\Enums\MeetingTypes;
use App\Entity\Database\Enums\OrganTypes;
use App\Entity\Database\SubDecision\Foundation;
use App\Tests\Support\BuildsDecisions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Foundation::class)]
class FoundationTest extends TestCase
{
    use BuildsDecisions;

    public function testFoundsAnOrganUnderItsTypeNameAndAbbreviation(): void
    {
        $foundation = $this->foundation($this->decision());
        $foundation->setOrganType(OrganTypes::Committee);
        $foundation->setName('Taartcommissie');
        $foundation->setAbbr('TC');

        self::assertSame(
            'Commissie Taartcommissie met afkorting TC wordt opgericht.',
            $foundation->getTranslatedContent($this->translator(), AppLanguages::Dutch),
        );
    }

    /**
     * A ballot committee is founded for one meeting, so it is named after that meeting and its purpose rather than
     * after an organ type and a name.
     */
    public function testFoundsABallotCommitteeAfterTheMeetingItServes(): void
    {
        $foundation = $this->foundation($this->decision($this->meeting(MeetingTypes::ALV, 42)));
        $foundation->setOrganType(OrganTypes::SC);
        $foundation->setName('Stemcommissie');
        $foundation->setPurpose('de verkiezing van het bestuur');
        $foundation->setAbbr('SC');

        self::assertSame(
            'De stemcommissie voor de verkiezing van het bestuur van de 42e ALV met afkorting SC wordt opgericht.',
            $foundation->getTranslatedContent($this->translator(), AppLanguages::Dutch),
        );
    }

    private function foundation(Decision $decision): Foundation
    {
        $foundation = new Foundation();
        $foundation->setSequence(1);
        $foundation->setDecision($decision);

        return $foundation;
    }
}
