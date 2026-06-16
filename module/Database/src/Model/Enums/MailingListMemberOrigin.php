<?php

declare(strict_types=1);

namespace Database\Model\Enums;

enum MailingListMemberOrigin: string
{
    case Manual = 'manual';
    case SyncMailman = 'sync_mailman';
    case SyncListmonk = 'sync_listmonk';
}
