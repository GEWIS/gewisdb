<?php

namespace Database\Service\Factory;

use Database\Form\Query as QueryForm;
use Database\Form\QueryExport as QueryExportForm;
use Database\Form\QuerySave as QuerySaveForm;
use Database\Mapper\SavedQuery as SavedQueryMapper;
use Database\Service\Query as QueryService;
use Doctrine\ORM\EntityManager;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class QueryFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     *
     * @return QueryService
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): QueryService {
        /** @var QueryForm $queryForm */
        $queryForm = $container->get(QueryForm::class);
        /** @var QueryExportForm $queryExportForm */
        $queryExportForm = $container->get(QueryExportForm::class);
        /** @var QuerySaveForm $querySaveForm */
        $querySaveForm = $container->get(QuerySaveForm::class);
        /** @var SavedQueryMapper $savedQueryMapper */
        $savedQueryMapper = $container->get(SavedQueryMapper::class);
        /** @var EntityManager $emReport */
        $emReport = $container->get('doctrine.entitymanager.orm_report');

        return new QueryService(
            $queryForm,
            $queryExportForm,
            $querySaveForm,
            $savedQueryMapper,
            $emReport
        );
    }
}
