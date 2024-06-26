<?php

declare(strict_types=1);

namespace Checker;

use Checker\Command\CheckAuthenticationKeysCommand;
use Checker\Command\CheckDatabaseCommand;
use Checker\Command\CheckDischargesCommand;
use Checker\Command\CheckMembershipExpirationCommand;
use Checker\Command\CheckMembershipGraduateRenewalCommand;
use Checker\Command\CheckMembershipTUeCommand;
use Checker\Command\CheckMembershipTypeCommand;

return [
    'laminas-cli' => [
        'commands' => [
            'check:database' => CheckDatabaseCommand::class,
            'check:discharges' => CheckDischargesCommand::class,
            'check:members:keys' => CheckAuthenticationKeysCommand::class,
            'check:membership:expiration' => CheckMembershipExpirationCommand::class,
            'check:membership:renewal:graduate' => CheckMembershipGraduateRenewalCommand::class,
            'check:membership:tue' => CheckMembershipTUeCommand::class,
            'check:membership:type' => CheckMembershipTypeCommand::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            'checker' => __DIR__ . '/../view/',
        ],
    ],
];
