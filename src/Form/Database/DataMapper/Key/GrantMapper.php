<?php

declare(strict_types=1);

namespace App\Form\Database\DataMapper\Key;

use App\Entity\Database\Decision;
use App\Entity\Database\Member;
use App\Entity\Database\SubDecision\Key\Granting;
use App\Form\Database\DataMapper\AbstractDecisionMapper;
use DateTime;
use Override;
use Symfony\Component\Form\FormInterface;

class GrantMapper extends AbstractDecisionMapper
{
    /**
     * @param array<string, FormInterface> $forms
     */
    #[Override]
    protected function mapSubDecisions(
        array $forms,
        Decision $decision,
    ): void {
        $grantee = $forms['grantee']->getData();
        $until = $forms['until']->getData();

        if (
            !$grantee instanceof Member
            || !$until instanceof DateTime
        ) {
            return;
        }

        $granting = new Granting();
        $granting->setSequence(1);
        $granting->setMember($grantee);
        $granting->setUntil($until);
        $granting->setDecision($decision);
    }
}
