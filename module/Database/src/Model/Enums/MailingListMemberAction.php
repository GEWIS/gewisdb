<?php

declare(strict_types=1);

namespace Database\Model\Enums;

enum MailingListMemberAction: string
{
    case Add = 'add';
    case Remove = 'remove';
}
