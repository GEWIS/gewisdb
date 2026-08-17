<?php

declare(strict_types=1);

namespace Database\Service\Factory;

use Database\Mapper\Meeting as MeetingMapper;
use Database\Service\Annulment as AnnulmentService;
use Laminas\Mvc\I18n\Translator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerInterface;

class AnnulmentFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    #[Override]
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): AnnulmentService {
        /** @var MeetingMapper $meetingMapper */
        $meetingMapper = $container->get(MeetingMapper::class);
        /** @var Translator $translator */
        $translator = $container->get(Translator::class);

        return new AnnulmentService($meetingMapper, $translator);
    }
}
