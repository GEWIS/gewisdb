<?php

declare(strict_types=1);

namespace App\Security\Api;

use App\Entity\User\Enums\ApiPermissions;
use Override;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Decides on {@see ApiPermissions} attributes, e.g. `#[IsGranted(ApiPermissions::MembersR->value)]`.
 *
 * The wildcard is not handled here: {@see \App\Entity\User\ApiPrincipal::can()} owns that rule, so a principal
 * holding `ApiPermissions::All` is granted every permission through exactly one implementation.
 */
final class ApiPermissionVoter extends Voter
{
    /**
     * Roles and other string attributes are none of this voter's business.
     */
    #[Override]
    public function supportsAttribute(string $attribute): bool
    {
        return null !== ApiPermissions::tryFrom($attribute);
    }

    #[Override]
    protected function supports(
        string $attribute,
        mixed $subject,
    ): bool {
        return null !== $this->asPermission($attribute);
    }

    #[Override]
    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null,
    ): bool {
        $permission = $this->asPermission($attribute);

        if (
            null === $permission
            || !($token instanceof ApiToken)
        ) {
            return false;
        }

        return $token->getApiPrincipal()->can($permission);
    }

    /**
     * Attributes reach a voter as strings, so a permission travels as its backing value and is mapped back here.
     */
    private function asPermission(string $attribute): ?ApiPermissions
    {
        return ApiPermissions::tryFrom($attribute);
    }
}
