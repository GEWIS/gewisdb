<?php

declare(strict_types=1);

namespace App\Command\Database;

use App\Service\Database\Member as MemberService;
use DateTime;
use InvalidArgumentException;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

use function assert;
use function is_string;
use function preg_match;

#[AsCommand(
    name: 'database:members:delete-expired',
    description: 'Delete members whose membership expired on or before the specified date.',
)]
class DeleteExpiredMembersCommand extends Command
{
    private const string OPTION_EXPIRATION = 'expiration';
    private const string EXPIRATION_PATTERN = '/^[0-9]{4}(-[0-9]{2}){2}$/';

    public function __construct(private readonly MemberService $memberService)
    {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this->addOption(
            self::OPTION_EXPIRATION,
            'e',
            InputOption::VALUE_REQUIRED,
            'Date of expiration (YYYY-MM-DD)',
        );
    }

    /**
     * The expiration date is mandatory but has no sensible default, so an interactive run asks for it rather than
     * failing outright.
     */
    #[Override]
    protected function interact(
        InputInterface $input,
        OutputInterface $output,
    ): void {
        if (null !== $input->getOption(self::OPTION_EXPIRATION)) {
            return;
        }

        $question = new Question('Date of expiration (YYYY-MM-DD): ');
        $question->setValidator($this->assertExpiration(...));

        $helper = $this->getHelper('question');
        assert($helper instanceof QuestionHelper);

        $input->setOption(
            self::OPTION_EXPIRATION,
            $helper->ask(
                $input,
                $output,
                $question,
            ),
        );
    }

    #[Override]
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $expiration = $this->assertExpiration($input->getOption(self::OPTION_EXPIRATION));

        $helper = $this->getHelper('question');
        assert($helper instanceof QuestionHelper);

        $question = new ConfirmationQuestion(
            '<error>Are you sure that ' . $expiration . ' is correct?</error>',
            false,
        );

        if (
            !$helper->ask(
                $input,
                $output,
                $question,
            )
        ) {
            $output->writeln('Not deleting expired members.');

            return Command::SUCCESS;
        }

        $output->writeln('Deleting expired members...');
        $this->memberService->removeExpiredMembers(new DateTime($expiration));

        return Command::SUCCESS;
    }

    private function assertExpiration(mixed $expiration): string
    {
        if (
            !is_string($expiration)
            || 1 !== preg_match(
                self::EXPIRATION_PATTERN,
                $expiration,
            )
        ) {
            throw new InvalidArgumentException('The expiration date must be of the form YYYY-MM-DD.');
        }

        return $expiration;
    }
}
