<?php

declare(strict_types=1);

namespace ApplicationTest;

use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\ToolsException;
use Laminas\ModuleManager\ModuleEvent;
use Laminas\ModuleManager\ModuleManager;
use Laminas\Mvc\Application;
use Laminas\Mvc\ApplicationInterface;
use Laminas\Mvc\Service\ServiceManagerConfig;
use Laminas\ServiceManager\ServiceManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use User\Model\User;

use function array_merge;
use function array_unique;

trait BaseControllerTrait
{
    protected ServiceManager $serviceManager;
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

        $this->serviceManager->setAllowOverride(true);
        $this->prepareDoctrine($this->serviceManager);
        // $this->setUpMockedServices();
        $this->serviceManager->setAllowOverride(false);

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

        // Perform the configuration override.
        /** @var ModuleManager $moduleManager */
        $moduleManager = $serviceManager->get('ModuleManager');
        $eventManager = $moduleManager->getEventManager();

        // Override the config before we load the modules. This is necessary as on loadModules.post the orm_default
        // EntityManager is automatically created to configure the listeners for Database -> Report automation.
        $eventManager->attach(ModuleEvent::EVENT_MERGE_CONFIG, static function (ModuleEvent $e): void {
            $configListener = $e->getConfigListener();
            $config = $configListener->getMergedConfig(false);
            $config = TestConfigProvider::overrideConfig($config);
            $configListener->setMergedConfig($config);
        });

        // Load modules
        $serviceManager->get('ModuleManager')->loadModules();

        return $serviceManager;
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws SchemaException
     * @throws ContainerExceptionInterface
     * @throws ToolsException
     */
    private function prepareDoctrine(ServiceManager $serviceManager): void
    {
        // For each connection type (database and report) create their relevant schemas in SQLite.
        foreach (['orm_default', 'orm_report'] as $value) {
            /** @var EntityManager $entityManager */
            $entityManager = $serviceManager->get('doctrine.entitymanager.' . $value);
            $metadata = $entityManager->getMetadataFactory()->getAllMetadata();

            if (empty($metadata)) {
                throw new SchemaException(
                    'No metadata classes to process for ' . $value,
                );
            }

            $tool = new SchemaTool($entityManager);
            $tool->createSchema($metadata);
        }
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
