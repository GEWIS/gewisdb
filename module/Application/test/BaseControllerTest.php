<?php

declare(strict_types=1);

namespace ApplicationTest;

use Laminas\Mvc\Application;
use Laminas\Mvc\ApplicationInterface;
use Laminas\Mvc\Service\ServiceManagerConfig;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use User\Model\User;

use function array_merge;
use function array_unique;

abstract class BaseControllerTest extends AbstractHttpControllerTestCase
{
    protected ServiceManager $serviceManager;
    protected MockObject $authService;
    protected MockObject $aclService;
    protected MockObject $userMapper;
    protected MockObject $memberMapper;

    protected const LIDNR = 8000;
    protected User $user;

    public function setUp(): void
    {
        $this->setApplicationConfig(TestConfigProvider::getConfig());

        parent::setUp();

        $this->getApplication();
    }

    protected function setUpMockedServices(): void
    {
    }

    public function getApplication(): ApplicationInterface
    {
        if ($this->application) {
            return $this->application;
        }

        $appConfig = $this->applicationConfig;

        $this->serviceManager = $this->initServiceManager($appConfig);

        // $this->serviceManager->setAllowOverride(true);
        // $this->setUpMockedServices();
        // $this->serviceManager->setAllowOverride(false);

        $this->application = $this->bootstrapApplication($this->serviceManager, $appConfig);

        $events = $this->application->getEventManager();
        $this->application->getServiceManager()->get('SendResponseListener')->detach($events);

        return $this->application;
    }

    /**
     * Variation of {@link Application::init} but without initial bootstrapping.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    private static function initServiceManager(array $configuration = []): ServiceManager
    {
        // Prepare the service manager
        $smConfig = $configuration['service_manager'] ?? [];
        $smConfig = new ServiceManagerConfig($smConfig);

        $serviceManager = new ServiceManager();
        $smConfig->configureServiceManager($serviceManager);
        $serviceManager->setService('ApplicationConfig', $configuration);

        // Load modules
        $serviceManager->get('ModuleManager')->loadModules();

        return $serviceManager;
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    private function bootstrapApplication(
        ServiceManager $serviceManager,
        array $configuration = [],
    ): Application {
        // Prepare list of listeners to bootstrap
        $listenersFromAppConfig = $configuration['listeners'] ?? [];
        $config = $serviceManager->get('config');
        $listenersFromConfigService = $config['listeners'] ?? [];

        $listeners = array_unique(array_merge($listenersFromConfigService, $listenersFromAppConfig));

        return $serviceManager->get('Application')->bootstrap($listeners);
    }
}
