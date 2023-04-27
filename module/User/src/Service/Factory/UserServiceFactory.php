<?php

declare(strict_types=1);

namespace User\Service\Factory;

use Laminas\Authentication\AuthenticationService;
use Laminas\Crypt\Password\PasswordInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use User\Form\Login;
use User\Form\UserCreate;
use User\Form\UserEdit;
use User\Mapper\UserMapper;
use User\Service\UserService;

class UserServiceFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): UserService {
        return new UserService(
            $container->get(UserMapper::class),
            $container->get(UserCreate::class),
            $container->get(Login::class),
            $container->get(UserEdit::class),
            $container->get(PasswordInterface::class),
            $container->get(AuthenticationService::class),
            $container->get('config'),
        );
    }
}
