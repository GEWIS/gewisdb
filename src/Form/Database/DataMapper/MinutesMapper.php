<?php

declare(strict_types=1);

namespace App\Form\Database\DataMapper;

use App\Entity\Database\Decision;
use App\Entity\Database\Meeting;
use App\Entity\Database\Member;
use App\Entity\Database\SubDecision\Minutes;
use Override;
use Symfony\Component\Form\FormInterface;

class MinutesMapper extends AbstractDecisionMapper
{
    /**
     * @param array<string, FormInterface> $forms
     */
    #[Override]
    protected function mapSubDecisions(
        array $forms,
        Decision $decision,
    ): void {
        $target = $forms['fmeeting']->getData();
        $author = $forms['author']->getData();

        if (
            !$target instanceof Meeting
            || !$author instanceof Member
        ) {
            return;
        }

        $subdecision = new Minutes();
        $subdecision->setTarget($target);
        $subdecision->setMember($author);
        $subdecision->setApproval((bool) $forms['approve']->getData());
        $subdecision->setChanges((bool) $forms['changes']->getData());
        $subdecision->setDecision($decision);
        $subdecision->setSequence(1);
    }
}
