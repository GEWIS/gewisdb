<?php

declare(strict_types=1);

namespace App\Exception\Database;

use RuntimeException;

/**
 * Thrown when a decision cannot be annulled, or when such an annulment cannot be taken back again.
 *
 * GEWISDB is a ledger: a decision can only be annulled while it is still the last word on the entities it affects.
 * Once another decision has built on it, annulling it (or undoing that annulment) no longer has a well-defined
 * outcome.
 */
class AnnulmentNotPossible extends RuntimeException
{
}
