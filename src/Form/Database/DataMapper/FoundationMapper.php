<?php

declare(strict_types=1);

namespace App\Form\Database\DataMapper;

use App\Entity\Database\Decision;
use App\Entity\Database\Enums\InstallationFunctions;
use App\Entity\Database\Enums\OrganTypes;
use App\Entity\Database\Member;
use App\Entity\Database\SubDecision\Foundation;
use App\Entity\Database\SubDecision\Installation;
use Override;
use Symfony\Component\Form\FormInterface;

use function in_array;
use function is_array;
use function is_string;
use function sprintf;

/**
 * Founding an organ is the foundation itself plus an installation for every founding member.
 */
class FoundationMapper extends AbstractDecisionMapper
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
        $name = $forms['name']->getData();
        $abbr = $forms['abbr']->getData();

        if (
            !$organType instanceof OrganTypes
            || !is_string($name)
            || !is_string($abbr)
        ) {
            return;
        }

        $foundation = new Foundation();
        $foundation->setSequence(1);
        $foundation->setOrganType($organType);
        // The name of a voting committee below is derived from the meeting it is founded in, which the sub-decision
        // only learns about from its decision.
        $foundation->setDecision($decision);

        if (OrganTypes::SC !== $organType) {
            $foundation->setName($name);
            $foundation->setAbbr($abbr);
        } else {
            $foundation->setName(sprintf(
                'Stemcommissie voor %s van de %de ALV',
                $name,
                $foundation->getMeetingNumber(),
            ));
            $foundation->setAbbr(sprintf(
                'SC%d-%s',
                $foundation->getMeetingNumber(),
                $abbr,
            ));
            $foundation->setPurpose($name);
        }

        $sequence = 2;
        $installedMembers = [];

        foreach ($forms['members']->getData() ?? [] as $entry) {
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

            // Holding a function in an organ does not make someone a member of it; that installation is recorded
            // separately, and only once however many functions the member ends up with.
            if (
                InstallationFunctions::Member !== $function
                && InstallationFunctions::InactiveMember !== $function
                && !in_array(
                    $member->getLidnr(),
                    $installedMembers,
                    true,
                )
            ) {
                $installation = new Installation();
                $installation->setSequence($sequence++);
                $installation->setFoundation($foundation);
                $installation->setFunction(InstallationFunctions::Member);
                $installation->setMember($member);
                $installation->setDecision($decision);

                $installedMembers[] = $member->getLidnr();
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
