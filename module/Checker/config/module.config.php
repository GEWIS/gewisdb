<?php

use Checker\Command\{
    CheckDatabaseCommand,
    CheckDischargesCommand,
    CheckMembershipsCommand,
};

return [
    'laminas-cli' => [
        'commands' => [
            'check:database' => CheckDatabaseCommand::class,
            'check:discharges' => CheckDischargesCommand::class,
            'check:memberships' => CheckMembershipsCommand::class,
        ],
    ],
];
