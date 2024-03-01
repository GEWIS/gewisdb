<?php

declare(strict_types=1);

namespace Checker\Service\Factory;

use Application\Service\Email as EmailService;
use Checker\Service\Birthday as BirthdayService;
use Database\Mapper\Member as MemberMapper;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\View\Renderer\PhpRenderer;
use Psr\Container\ContainerInterface;

class BirthdayFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): BirthdayService {
        return new BirthdayService(
            $container->get(PhpRenderer::class),
            $container->get(MemberMapper::class),
            $container->get(EmailService::class),
        );
    }
}
