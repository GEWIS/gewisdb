<?php

declare(strict_types=1);

namespace App\Form\Database\DataMapper;

use App\Entity\Database\Decision;
use App\Entity\Database\Enums\OrganTypes;
use App\Entity\Database\Member;
use App\Entity\Database\SubDecision\OrganRegulation;
use DateTime;
use Override;
use Symfony\Component\Form\FormInterface;

use function is_string;

class OrganRegulationMapper extends AbstractDecisionMapper
{
    /**
     * @param array<string, FormInterface> $forms
     */
    #[Override]
    protected function mapSubDecisions(
        array $forms,
        Decision $decision,
    ): void {
        $organType = $forms['type']->getData();
        $abbr = $forms['abbr']->getData();
        $date = $forms['date']->getData();
        $author = $forms['author']->getData();
        $version = $forms['version']->getData();

        if (
            !$organType instanceof OrganTypes
            || !is_string($abbr)
            || !$date instanceof DateTime
            || !$author instanceof Member
            || !is_string($version)
        ) {
            return;
        }

        // Only organs that can have organ regulations at all. The form rejects the others with a message of their
        // own, so building nothing here leaves that message to do the talking.
        if (!$organType->hasOrganRegulations()) {
            return;
        }

        $subdecision = new OrganRegulation();
        $subdecision->setSequence(1);
        $subdecision->setOrganType($organType);
        $subdecision->setDate($date);
        $subdecision->setAbbr($abbr);
        $subdecision->setMember($author);
        $subdecision->setVersion($version);
        $subdecision->setApproval((bool) $forms['approve']->getData());
        $subdecision->setChanges((bool) $forms['changes']->getData());
        $subdecision->setDecision($decision);
    }
}
