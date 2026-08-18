<?php

declare(strict_types=1);

namespace App\Command\Checker;

use App\Service\Checker\Checker as CheckerService;
use Symfony\Component\Console\Command\Command;

abstract class AbstractCheckerCommand extends Command
{
    public function __construct(protected readonly CheckerService $checkerService)
    {
        parent::__construct();
    }
}
