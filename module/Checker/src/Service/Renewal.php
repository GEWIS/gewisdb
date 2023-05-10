<?php

declare(strict_types=1);

namespace Checker\Service;

use Application\Service\Email as EmailService;
use Checker\Mapper\Member as MemberMapper;
use Database\Mapper\ActionLink as ActionLinkMapper;

/**
 * Renewal class that takes care of renewing graduates
 * and converting memberships to graduates
 */
class Renewal
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function __construct(
        private readonly ActionLinkMapper $actionLinkMapper,
        private readonly MemberMapper $memberMapper,
        private readonly EmailService $emailService,
        private readonly array $config,
    ) {
    }

    /**
     * Send emails to expiring graduates
     */
    public function sendRenewalGraduates(): void
    {
        $graduates = $this->memberMapper->getExpiringGraduates();

        // TODO
        foreach ($graduates as $graduate) {
            echo $this->emailService->getEmailBody();
        }
    }
}
