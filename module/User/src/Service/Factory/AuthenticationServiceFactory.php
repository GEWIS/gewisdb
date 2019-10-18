<?php

namespace User\Service\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Authentication\AuthenticationService;
use Zend\Crypt\Password\PasswordInterface;

class AuthenticationServiceFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        $service = $sm->get('doctrine.authenticationservice.orm_default');
        $passwordVerify = $sm->get(PasswordInterface::class);

        $service->getAdapter()
            ->getOptions()
            ->setCredentialCallable(function($identity, $credential) use ($passwordVerify) {
                return $passwordVerify->verify($credential, $identity->getPassword());
            });

        return $service;
    }
}
