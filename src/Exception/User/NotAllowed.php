<?php

declare(strict_types=1);

namespace App\Exception\User;

use App\Entity\User\Enums\ApiPermissions;
use Throwable;

class NotAllowed extends ApiException
{
    public function __construct(
        ApiPermissions $permission,
        int $code = 0,
        ?Throwable $previousThrowable = null,
    ) {
        $message = 'Permission `' . $permission->getString() . '` is needed but is not currently held.';

        parent::__construct(
            $message,
            $code,
            $previousThrowable,
        );
    }
}
