<?php

declare(strict_types=1);

namespace User\Service\Factory;

use Laminas\Authentication\AuthenticationService;
use Laminas\Crypt\Password\PasswordInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use User\Form\Login as LoginForm;
use User\Form\UserCreate as UserCreateForm;
use User\Form\UserEdit as UserEditForm;
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
            $container->get(UserCreateForm::class),
            $container->get(LoginForm::class),
            $container->get(UserEditForm::class),
            $container->get(UserMapper::class),
            $container->get(AuthenticationService::class),
            $container->get(PasswordInterface::class),
            $container->get('config'),
        );
    }
}
