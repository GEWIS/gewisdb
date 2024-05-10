<?php

declare(strict_types=1);

namespace Checker\Command;

use Checker\Service\Renewal as RenewalService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'check:membership:renewal:graduate',
    description: 'Check graduates who are expiring at the end of the association year and send renewal emails.',
)]
class CheckMembershipGraduateRenewalCommand extends Command
{
    public function __construct(private readonly RenewalService $renewalService)
    {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $this->getRenewalService()->sendRenewalGraduates();

        return Command::SUCCESS;
    }

    public function getRenewalService(): RenewalService
    {
        return $this->renewalService;
    }
}
