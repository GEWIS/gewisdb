<?php

declare(strict_types=1);

namespace Database\Command;

use Database\Service\Member as MemberService;
use DateTime;
use Laminas\Cli\Command\AbstractParamAwareCommand;
use Laminas\Cli\Input\ParamAwareInputInterface;
use Laminas\Cli\Input\StringParam;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class DeleteExpiredMembersCommand extends AbstractParamAwareCommand
{
    /** @var string $defaultName */
    protected static $defaultName = 'database:members:delete-expired';
    /** @var string $defaultDescription */
    protected static $defaultDescription = 'Delete members whose membership expired on or before the specified date.';

    public function __construct(private readonly MemberService $memberService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addParam(
            (new StringParam('expiration'))
                ->setPattern('/^[0-9]{4}(-[0-9]{2}){2}$/')
                ->setDescription('Date of expiration (YYYY-MM-DD)')
                ->setShortcut('e')
                ->setRequiredFlag(true),
        );
    }

    /**
     * @param ParamAwareInputInterface $input
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $expiration = $input->getParam('expiration');

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('<error>Are you sure that ' . $expiration . ' is correct?</error>', false);

        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('Not deleting expired members.');

            return Command::SUCCESS;
        }

        $output->writeln('Deleting expired members...');
        $this->memberService->removeExpiredMembers(new DateTime($expiration));

        return Command::SUCCESS;
    }
}
