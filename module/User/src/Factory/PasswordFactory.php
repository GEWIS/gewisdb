<?php

declare(strict_types=1);

namespace User\Factory;

use Laminas\Crypt\Password\Bcrypt;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class PasswordFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): Bcrypt {
        return new Bcrypt(['cost' => 12]);
    }
}
