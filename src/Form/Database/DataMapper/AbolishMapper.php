<?php

declare(strict_types=1);

namespace App\Form\Database\DataMapper;

use App\Entity\Database\Decision;
use App\Entity\Database\SubDecision\Abrogation;
use App\Entity\Database\SubDecision\Discharge;
use App\Entity\Database\SubDecision\Foundation;
use App\Entity\Database\SubDecision\Installation;
use Override;
use Symfony\Component\Form\FormInterface;

use function array_reverse;

/**
 * Abolishing an organ discharges everyone still installed in it and then abrogates its foundation.
 */
class AbolishMapper extends AbstractDecisionMapper
{
    /**
     * @param array<string, FormInterface> $forms
     */
    #[Override]
    protected function mapSubDecisions(
        array $forms,
        Decision $decision,
    ): void {
        $foundation = $forms['subdecision']->getData();

        if (!$foundation instanceof Foundation) {
            return;
        }

        $installations = [];

        foreach ($foundation->getReferences() as $reference) {
            if (
                !($reference instanceof Installation)
                || null !== $reference->getDischarge()
            ) {
                continue;
            }

            $installations[] = $reference;
        }

        // Members leave the organ in the reverse of the order they joined it.
        $installations = array_reverse($installations);

        $sequence = 1;

        foreach ($installations as $installation) {
            $discharge = new Discharge();
            $discharge->setInstallation($installation);
            $discharge->setSequence($sequence++);
            $discharge->setDecision($decision);
        }

        $abrogation = new Abrogation();
        $abrogation->setFoundation($foundation);
        $abrogation->setSequence($sequence);
        $abrogation->setDecision($decision);
    }
}
