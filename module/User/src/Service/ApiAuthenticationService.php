<?php

namespace User\Service;

use Laminas\Authentication\{
    Adapter\AdapterInterface,
    AuthenticationService,
    AuthenticationServiceInterface,
    Result,
};
use RuntimeException;
use User\Adapter\ApiPrincipalAdapter;

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

        throw new RuntimeException(
            'ApiAuthenticationService expects the authentication adapter to be of type ApiPrincipalAdapter.'
        );
    }
}
