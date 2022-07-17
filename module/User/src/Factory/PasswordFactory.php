<?php

namespace User\Factory;

use Laminas\Crypt\Password\Bcrypt;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class PasswordFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     *
     * @return Bcrypt
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null,
    ): Bcrypt {
        return new Bcrypt([
            'cost' => 12,
        ]);
    }
}
