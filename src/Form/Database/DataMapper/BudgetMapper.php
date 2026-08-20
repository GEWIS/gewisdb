<?php

declare(strict_types=1);

namespace App\Form\Database\DataMapper;

use App\Entity\Database\Decision;
use App\Entity\Database\Member;
use App\Entity\Database\SubDecision\Financial\Budget;
use App\Entity\Database\SubDecision\Financial\Statement;
use DateTime;
use Override;
use Symfony\Component\Form\FormInterface;

use function is_string;

class BudgetMapper extends AbstractDecisionMapper
{
    /**
     * @param array<string, FormInterface> $forms
     */
    #[Override]
    protected function mapSubDecisions(
        array $forms,
        Decision $decision,
    ): void {
        $name = $forms['name']->getData();
        $date = $forms['date']->getData();
        $author = $forms['author']->getData();
        $version = $forms['version']->getData();

        if (
            !is_string($name)
            || !$date instanceof DateTime
            || !$author instanceof Member
            || !is_string($version)
        ) {
            return;
        }

        $subdecision = 'budget' === $forms['type']->getData()
            ? new Budget()
            : new Statement();

        $subdecision->setSequence(1);
        $subdecision->setDate($date);
        $subdecision->setName($name);
        $subdecision->setMember($author);
        $subdecision->setVersion($version);
        $subdecision->setApproval((bool) $forms['approve']->getData());
        $subdecision->setChanges((bool) $forms['changes']->getData());
        $subdecision->setDecision($decision);
    }
}
