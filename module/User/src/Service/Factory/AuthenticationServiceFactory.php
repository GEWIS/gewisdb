<?php

namespace User\Service\Factory;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Crypt\Password\PasswordInterface;

class AuthenticationServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     *
     * @return mixed|object
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ) {
        $service = $container->get('doctrine.authenticationservice.orm_default');
        $passwordVerify = $container->get(PasswordInterface::class);

        $service->getAdapter()
            ->getOptions()
            ->setCredentialCallable(function ($identity, $credential) use ($passwordVerify) {
                return $passwordVerify->verify($credential, $identity->getPassword());
            });

        return $service;
    }
}
