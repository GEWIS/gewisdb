<?php

namespace Checker\Command;

use Checker\Service\Checker as CheckerService;
use Symfony\Component\Console\Command\Command;

abstract class AbstractCheckerCommand extends Command
{
    private CheckerService $checkerService;

    public function __construct(CheckerService $checkerService)
    {
        $this->checkerService = $checkerService;

        parent::__construct();
    }

    public function getCheckerService(): CheckerService
    {
        return $this->checkerService;
    }
}
