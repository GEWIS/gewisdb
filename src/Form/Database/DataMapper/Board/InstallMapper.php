<?php

declare(strict_types=1);

namespace App\Form\Database\DataMapper\Board;

use App\Entity\Database\Decision;
use App\Entity\Database\Enums\BoardFunctions;
use App\Entity\Database\Member;
use App\Entity\Database\SubDecision\Board\Installation;
use App\Form\Database\DataMapper\AbstractDecisionMapper;
use DateTime;
use Override;
use Symfony\Component\Form\FormInterface;

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
        $member = $forms['member']->getData();
        $function = $forms['function']->getData();
        $date = $forms['date']->getData();

        if (
            !$member instanceof Member
            || !$function instanceof BoardFunctions
            || !$date instanceof DateTime
        ) {
            return;
        }

        $installation = new Installation();
        $installation->setSequence(1);
        $installation->setMember($member);
        $installation->setFunction($function);
        $installation->setDate($date);
        $installation->setDecision($decision);
    }
}
