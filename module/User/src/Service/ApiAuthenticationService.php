<?php

declare(strict_types=1);

namespace User\Service;

use InvalidArgumentException;
use Laminas\Authentication\{
    Adapter\AdapterInterface,
    AuthenticationService,
    AuthenticationServiceInterface,
    Result,
};
use Laminas\Stdlib\ResponseInterface as Response;
use User\Adapter\ApiPrincipalAdapter;
use User\Model\Enums\ApiPermissions;
use User\Model\Exception\NotAllowed as NotAllowedException;

class ApiAuthenticationService extends AuthenticationService implements AuthenticationServiceInterface
{
    /**
     * Constructor without storage
     */
    public function __construct(?AdapterInterface $adapter = null)
    {
        parent::__construct(null, $adapter);
    }

    public function setAdapter(AdapterInterface $adapter): self
    {
        if ($adapter instanceof ApiPrincipalAdapter) {
            $this->adapter = $adapter;

            return $this;
        }

        throw new InvalidArgumentException(
            'ApiAuthenticationService expects the authentication adapter to be of type ApiPrincipalAdapter.'
        );
    }

    private function currentUserCan(ApiPermissions $permission): bool
    {
        if (!$this->hasIdentity()) {
            return false;
        }

        return $this->getIdentity()->can($permission);
    }

    /**
     * Function that asserts that a principal has the required permissions to perform a given action
     * @throws NotAllowedException if not
     */
    public function assertCan(ApiPermissions $permission): void
    {
        $this->currentUserCan($permission) or throw new NotAllowedException($permission);
    }
}
