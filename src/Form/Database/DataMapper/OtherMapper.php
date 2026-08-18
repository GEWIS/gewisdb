<?php

declare(strict_types=1);

namespace App\Form\Database\DataMapper;

use App\Entity\Database\Decision;
use App\Entity\Database\SubDecision\Other;
use Override;
use Symfony\Component\Form\FormInterface;

use function is_string;

class OtherMapper extends AbstractDecisionMapper
{
    /**
     * @param array<string, FormInterface> $forms
     */
    #[Override]
    protected function mapSubDecisions(
        array $forms,
        Decision $decision,
    ): void {
        $content = $forms['content']->getData();

        if (!is_string($content)) {
            return;
        }

        $subdecision = new Other();
        $subdecision->setSequence(1);
        $subdecision->setContent($content);
        $subdecision->setDecision($decision);
    }
}
