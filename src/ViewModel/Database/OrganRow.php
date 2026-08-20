<?php

declare(strict_types=1);

namespace App\ViewModel\Database;

use App\Entity\Database\SubDecision\Installation;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * One organ a member is installed in, as it is listed on their page.
 *
 * The installation names the organ through the foundation it references, and that foundation is also what identifies
 * the organ's page: five coordinates that the member page would otherwise have to walk out of the decision graph.
 */
final readonly class OrganRow
{
    private function __construct(
        public string $abbr,
        public string $url,
    ) {
    }

    public static function fromInstallation(
        Installation $installation,
        UrlGeneratorInterface $urlGenerator,
    ): self {
        $foundation = $installation->getFoundation();

        return new self(
            $foundation->getAbbr(),
            $urlGenerator->generate(
                'decision_organ_view',
                [
                    'type' => $foundation->getMeetingType()->value,
                    'number' => $foundation->getMeetingNumber(),
                    'point' => $foundation->getDecisionPoint(),
                    'decision' => $foundation->getDecisionNumber(),
                    'sequence' => $foundation->getSequence(),
                ],
            ),
        );
    }
}
