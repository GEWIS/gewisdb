<?php

namespace Api\Service\Factory;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Api\Service\ApiKey as ApiKeyService;
use Api\Mapper\ApiKey as ApiKeyMapper;
use Api\Form\ApiKey as ApiKeyForm;

class ApiKeyFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        $form = new ApiKeyForm();
        $form->setHydrator(new \Application\Doctrine\Hydrator\DoctrineObject(
            $sm->get('database_doctrine_em')
        ));

        return new ApiKeyService(
            $sm->get(ApiKeyMapper::class),
            $form
        );
    }
}
