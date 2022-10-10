<?php

use Checker\Command\{
    CheckAuthenticationKeysCommand,
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
            'check:members:keys' => CheckAuthenticationKeysCommand::class,
            'check:membership:expiration' => CheckMembershipExpirationCommand::class,
            'check:membership:tue' => CheckMembershipTUeCommand::class,
            'check:membership:type' => CheckMembershipTypeCommand::class,
        ],
    ],
];
