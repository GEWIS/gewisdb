<?php

declare(strict_types=1);

namespace Database\Controller\Factory;

use Database\Controller\MeetingController;
use Database\Form\Fieldset\MemberFunction as MemberFunctionFieldset;
use Database\Service\Api as ApiService;
use Database\Service\Meeting as MeetingService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class MeetingControllerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): MeetingController {
        $apiService = $container->get(ApiService::class);
        $meetingService = $container->get(MeetingService::class);
        /** @var MemberFunctionFieldset $memberFunctionFieldset */
        $memberFunctionFieldset = $container->get('database_form_fieldset_memberfunction_nomember');

        return new MeetingController(
            $apiService,
            $meetingService,
            $memberFunctionFieldset,
        );
    }
}
