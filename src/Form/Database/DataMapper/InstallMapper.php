<?php

declare(strict_types=1);

namespace App\Form\Database\DataMapper;

use App\Entity\Database\Decision;
use App\Entity\Database\Enums\InstallationFunctions;
use App\Entity\Database\Member;
use App\Entity\Database\SubDecision\Discharge;
use App\Entity\Database\SubDecision\Foundation;
use App\Entity\Database\SubDecision\Installation;
use App\Entity\Database\SubDecision\Reappointment;
use Override;
use Symfony\Component\Form\FormInterface;

use function is_array;

/**
 * Changing an organ's membership in one decision.
 *
 * The three kinds of change are numbered in a fixed order — reappointments, then discharges, then installations —
 * because the resulting decision has to read as one coherent statement.
 */
class InstallMapper extends AbstractDecisionMapper
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

        $sequence = 1;

        foreach ($forms['reappointments']->getData() ?? [] as $installation) {
            if (!$installation instanceof Installation) {
                continue;
            }

            $reappointment = new Reappointment();
            $reappointment->setInstallation($installation);
            $reappointment->setSequence($sequence++);
            $reappointment->setDecision($decision);
        }

        foreach ($forms['discharges']->getData() ?? [] as $installation) {
            if (!$installation instanceof Installation) {
                continue;
            }

            $discharge = new Discharge();
            $discharge->setInstallation($installation);
            $discharge->setSequence($sequence++);
            $discharge->setDecision($decision);
        }

        foreach ($forms['installations']->getData() ?? [] as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $member = $entry['member'] ?? null;
            $function = $entry['function'] ?? null;

            if (
                !$member instanceof Member
                || !$function instanceof InstallationFunctions
            ) {
                continue;
            }

            $installation = new Installation();
            $installation->setSequence($sequence++);
            $installation->setFoundation($foundation);
            $installation->setFunction($function);
            $installation->setMember($member);
            $installation->setDecision($decision);
        }
    }
}
