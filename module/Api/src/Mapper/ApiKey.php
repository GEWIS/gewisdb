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
     * Find an API key by id.
     * @return ApiKeyModel
     */
    public function find($id)
    {
        return $this->getRepository()->find($id);
    }

    /**
     * Persist.
     * @param ApiKeyModel $apikey
     */
    public function persist(ApiKeyModel $apikey)
    {
        $this->em->persist($apikey);
        $this->em->flush();
    }

    /**
     * Remove.
     * @param ApiKeyModel $apikey
     */
    public function remove(ApiKeyModel $apikey)
    {
        $this->em->remove($apikey);
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
