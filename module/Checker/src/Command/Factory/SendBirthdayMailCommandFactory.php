<?php
declare(strict_types=1);

namespace Checker\Command\Factory;

use Checker\Command\SendBirthdayMailCommand;
use Checker\Service\Birthday as BirthdayService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class SendBirthdayMailCommandFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): SendBirthdayMailCommand {
        return new SendBirthdayMailCommand(
            $container->get(BirthdayService::class),
        );
    }
}
