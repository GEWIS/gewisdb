<?php

namespace User\Service\Factory;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;
use User\Service\UserService;
use User\Mapper\UserMapper;
use User\Form\UserCreate;
use User\Form\Login;
use Zend\Authentication\AuthenticationService;
use Zend\Crypt\Password\PasswordInterface;
use User\Form\UserEdit;

class UserServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     *
     * @return UserService
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): UserService {
        return new UserService(
            $container->get(UserMapper::class),
            $container->get(UserCreate::class),
            $container->get(Login::class),
            $container->get(UserEdit::class),
            $container->get(PasswordInterface::class),
            $container->get(AuthenticationService::class)
        );
    }
}
