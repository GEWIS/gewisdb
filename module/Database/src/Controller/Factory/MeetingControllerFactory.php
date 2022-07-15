<?php

namespace Database\Controller\Factory;

use Database\Controller\MeetingController;
use Database\Form\Fieldset\MemberFunction as MemberFunctionFieldset;
use Database\Service\Meeting as MeetingService;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class MeetingControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     *
     * @return MeetingController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null,
    ): MeetingController {
        /** @var MeetingService $meetingService */
        $meetingService = $container->get(MeetingService::class);
        /** @var MemberFunctionFieldset $memberFunctionFieldset */
        $memberFunctionFieldset = $container->get('database_form_fieldset_memberfunction_nomember');

        return new MeetingController(
            $meetingService,
            $memberFunctionFieldset,
        );
    }
}
