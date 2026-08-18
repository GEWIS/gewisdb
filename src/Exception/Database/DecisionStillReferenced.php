<?php

declare(strict_types=1);

namespace App\Exception\Database;

use RuntimeException;

/**
 * Thrown when a decision cannot be deleted because later decisions still point at what it brought about.
 *
 * Those references have to be taken back first, in the reverse order they were recorded; deleting the decision under
 * them would leave the ledger with sub-decisions that refer to nothing.
 */
class DecisionStillReferenced extends RuntimeException
{
}
