<?php

declare(strict_types=1);

namespace Database\Service\Factory;

use Application\Service\Config as ConfigService;
use Database\Mapper\MailingListMember as MailingListMemberMapper;
use Database\Service\Mailman as MailmanService;
use Laminas\Cache\Storage\Adapter\AbstractAdapter;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class MailmanFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): MailmanService {
        /** @var AbstractAdapter $mailmanCache */
        $mailmanCache = $container->get('database_cache_mailman');
        /** @var MailingListMemberMapper $mailingListMemberMapper */
        $mailingListMemberMapper = $container->get(MailingListMemberMapper::class);
        /** @var ConfigService $configService */
        $configService = $container->get(ConfigService::class);
        /** @var array $mailmanConfig */
        $mailmanConfig = $container->get('config')['mailman_api'];

        return new MailmanService(
            $mailmanCache,
            $mailingListMemberMapper,
            $configService,
            $mailmanConfig,
        );
    }
}
