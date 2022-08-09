<?php

namespace Checker\Command;

use Checker\Service\Checker as CheckerService;
use Symfony\Component\Console\Command\Command;

abstract class AbstractCheckerCommand extends Command
{
    public function __construct(private readonly CheckerService $checkerService)
    {
        parent::__construct();
    }

    public function getCheckerService(): CheckerService
    {
        return $this->checkerService;
    }
}
