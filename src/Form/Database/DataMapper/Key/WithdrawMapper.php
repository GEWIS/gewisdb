<?php

declare(strict_types=1);

namespace App\Form\Database\DataMapper\Key;

use App\Entity\Database\Decision;
use App\Entity\Database\SubDecision\Key\Granting;
use App\Entity\Database\SubDecision\Key\Withdrawal;
use App\Form\Database\DataMapper\AbstractDecisionMapper;
use DateTime;
use Override;
use Symfony\Component\Form\FormInterface;

class WithdrawMapper extends AbstractDecisionMapper
{
    /**
     * @param array<string, FormInterface> $forms
     */
    #[Override]
    protected function mapSubDecisions(
        array $forms,
        Decision $decision,
    ): void {
        $granting = $forms['subdecision']->getData();
        $withdrawOn = $forms['withdrawOn']->getData();

        if (
            !$granting instanceof Granting
            || !$withdrawOn instanceof DateTime
        ) {
            return;
        }

        $withdrawal = new Withdrawal();
        $withdrawal->setSequence(1);
        $withdrawal->setGranting($granting);
        $withdrawal->setWithdrawnOn($withdrawOn);
        $withdrawal->setDecision($decision);
    }
}
