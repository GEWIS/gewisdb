<?php

declare(strict_types=1);

namespace App\Form\Database\DataMapper\Board;

use App\Entity\Database\Decision;
use App\Entity\Database\SubDecision\Board\Discharge;
use App\Entity\Database\SubDecision\Board\Installation;
use App\Form\Database\DataMapper\AbstractDecisionMapper;
use Override;
use Symfony\Component\Form\FormInterface;

class DischargeMapper extends AbstractDecisionMapper
{
    /**
     * @param array<string, FormInterface> $forms
     */
    #[Override]
    protected function mapSubDecisions(
        array $forms,
        Decision $decision,
    ): void {
        $installation = $forms['subdecision']->getData();

        if (!$installation instanceof Installation) {
            return;
        }

        $discharge = new Discharge();
        $discharge->setSequence(1);
        $discharge->setInstallation($installation);
        $discharge->setDecision($decision);
    }
}
