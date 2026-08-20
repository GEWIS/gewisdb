<?php

declare(strict_types=1);

namespace App\ViewModel\Database;

use App\Entity\Database\SubDecision\Board\Installation as BoardInstallation;
use App\Entity\Database\SubDecision\Key\Granting;

/**
 * What the decision forms let a decision be taken about: the board and the key codes as they stand right now.
 *
 * These are sub-decisions rather than plain choices because a relief, a discharge or a withdrawal refers to the
 * sub-decision it undoes.
 */
final readonly class DecisionOptions
{
    /**
     * @param BoardInstallation[] $boardInstallations
     * @param BoardInstallation[] $releasableBoardInstallations board members who have not been relieved yet
     * @param Granting[]          $keyGrants
     */
    public function __construct(
        public array $boardInstallations,
        public array $releasableBoardInstallations,
        public array $keyGrants,
    ) {
    }
}
