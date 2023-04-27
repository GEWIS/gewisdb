<?php

declare(strict_types=1);

namespace User\Service\Factory;

use Laminas\Authentication\AuthenticationService;
use Laminas\Crypt\Password\PasswordInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class AuthenticationServiceFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): AuthenticationService {
        /** @var AuthenticationService $service */
        $service = $container->get('doctrine.authenticationservice.orm_default');
        $passwordVerify = $container->get(PasswordInterface::class);

        $service->getAdapter()
            ->getOptions()
            ->setCredentialCallable(static function ($identity, $credential) use ($passwordVerify) {
                return $passwordVerify->verify($credential, $identity->getPassword());
            });

        return $service;
    }
}
