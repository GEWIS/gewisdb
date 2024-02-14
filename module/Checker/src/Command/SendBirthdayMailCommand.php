<?php

declare(strict_types=1);

namespace Checker\Command;

use Checker\Service\Birthday as BirthdayService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendBirthdayMailCommand extends Command
{
    /** @var string $defaultName */
    protected static $defaultName = 'send:birthday:mail:command';

    /** @var string $defaultDescription*/
    protected static $defaultDescription =
        'Send birthday mails to all those that have their birthday';

    public function __construct(private readonly BirthdayService $birthdayService)
    {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $this->getBirthdayService()->sendBirthday();

        return Command::SUCCESS;
    }

    public function getBirthdayService(): BirthdayService
    {
        return $this->birthdayService;
    }
}
