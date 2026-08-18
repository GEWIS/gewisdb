<?php

declare(strict_types=1);

namespace App\Security\Api;

use App\Entity\User\Enums\ApiPermissions;
use Override;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

use function is_string;

/**
 * Decides on {@see ApiPermissions} attributes, e.g. `#[IsGranted(ApiPermissions::MembersR)]`.
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
        mixed $attribute,
        mixed $subject,
    ): bool {
        return null !== $this->asPermission($attribute);
    }

    #[Override]
    protected function voteOnAttribute(
        mixed $attribute,
        mixed $subject,
        TokenInterface $token,
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
     * Accepts both the enum itself and its backing value, so a permission can also be expressed as a plain string.
     */
    private function asPermission(mixed $attribute): ?ApiPermissions
    {
        if ($attribute instanceof ApiPermissions) {
            return $attribute;
        }

        if (is_string($attribute)) {
            return ApiPermissions::tryFrom($attribute);
        }

        return null;
    }
}
