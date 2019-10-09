<?php

namespace Api\Mapper;

use Api\Model\ApiKey as ApiKeyModel;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;

class ApiKey
{

    /**
     * Doctrine entity manager.
     *
     * @var EntityManager
     */
    protected $em;


    /**
     * Constructor.
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Find all API keys.
     * @return ApiKeyModel[]
     */
    public function findAll()
    {
        return $this->getRepository()->findAll();
    }

    /**
     * Persist.
     * @param ApiKeyModel $apikey
     */
    public function persist(ApiKeyModel $apiKey)
    {
        $this->em->persist($apiKey);
        $this->em->flush();
    }

    /**
     * Get the repository for this mapper.
     * @return Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository(ApiKeyModel::class);
    }
}
