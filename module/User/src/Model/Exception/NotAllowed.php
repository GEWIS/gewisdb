<?php

declare(strict_types=1);

namespace User\Model\Exception;

use RuntimeException;
use Throwable;
use User\Model\Enums\ApiPermissions;

class NotAllowed extends RuntimeException
{
    /**
     * @param ApiPermissions $permission
     * @param int $code
     * @param ?Throwable $previousThrowable
     */
    public function __construct(
        ApiPermissions $permission,
        int $code = 0,
        Throwable $previousThrowable = null,
    ) {
        $message = "Permission `" . $permission->getString() . "` is needed but is not currently held.";
        parent::__construct($message, $code, $previousThrowable);
    }
}
