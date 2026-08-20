<?php

declare(strict_types=1);

namespace App\Form\Database\DataMapper;

use App\Entity\Database\Decision;
use App\Entity\Database\SubDecision\Annulment;
use Override;
use Symfony\Component\Form\FormInterface;

class AnnulmentMapper extends AbstractDecisionMapper
{
    /**
     * @param array<string, FormInterface> $forms
     */
    #[Override]
    protected function mapSubDecisions(
        array $forms,
        Decision $decision,
    ): void {
        $target = $forms['fdecision']->getData();

        if (!$target instanceof Decision) {
            return;
        }

        $annulment = new Annulment();
        $annulment->setTarget($target);
        $annulment->setSequence(0);
        $annulment->setDecision($decision);
    }
}
