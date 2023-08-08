<?php

declare(strict_types=1);

namespace Database\Command;

use Database\Service\Member as MemberService;
use Laminas\Cli\Command\AbstractParamAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteExpiredProspectiveMembersCommand extends AbstractParamAwareCommand
{
    /** @var string $defaultName */
    protected static $defaultName = 'database:prospective-members:delete-expired';
    /** @var string $defaultDescription */
    protected static $defaultDescription = 'Delete prospective members whose Checkout Session has expired or failed.';

    public function __construct(private readonly MemberService $memberService)
    {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $output->writeln('Deleting expired prospective members...');
        $this->memberService->removeExpiredProspectiveMembers();

        return Command::SUCCESS;
    }
}
