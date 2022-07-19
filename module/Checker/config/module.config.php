<?php

use Checker\Command\{
    CheckDatabaseCommand,
    CheckDischargesCommand,
    CheckMembershipExpirationCommand,
    CheckMembershipTUeCommand,
    CheckMembershipTypeCommand,
};

return [
    'laminas-cli' => [
        'commands' => [
            'check:database' => CheckDatabaseCommand::class,
            'check:discharges' => CheckDischargesCommand::class,
            'check:membership:expiration' => CheckMembershipExpirationCommand::class,
            'check:membership:tue' => CheckMembershipTUeCommand::class,
            'check:membership:type' => CheckMembershipTypeCommand::class,
        ],
    ],
];
