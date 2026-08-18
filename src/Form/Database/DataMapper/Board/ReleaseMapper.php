<?php

declare(strict_types=1);

namespace App\Form\Database\DataMapper\Board;

use App\Entity\Database\Decision;
use App\Entity\Database\SubDecision\Board\Installation;
use App\Entity\Database\SubDecision\Board\Release;
use App\Form\Database\DataMapper\AbstractDecisionMapper;
use DateTime;
use Override;
use Symfony\Component\Form\FormInterface;

class ReleaseMapper extends AbstractDecisionMapper
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
        $date = $forms['date']->getData();

        if (
            !$installation instanceof Installation
            || !$date instanceof DateTime
        ) {
            return;
        }

        $release = new Release();
        $release->setSequence(1);
        $release->setInstallation($installation);
        $release->setDate($date);
        $release->setDecision($decision);
    }
}
