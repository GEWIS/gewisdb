<?php

namespace Api\Service;

use Api\Mapper\ApiKey as ApiKeyMapper;
use Api\Model\ApiKey as ApiKeyModel;
use Api\Form\ApiKey as ApiKeyForm;
use Zend\Math\Rand;

class ApiKey
{

    /**
     * @var ApiKeyMapper
     */
    protected $mapper;

    /**
     * @var ApiKeyForm
     */
    protected $apiKeyForm;

    /**
     * Constructor.
     *
     * @param ApiKeyMapper $mapper
     * @param ApiKeyForm $apiKeyForm
     */
    public function __construct(ApiKeyMapper $mapper, ApiKeyForm $apiKeyForm)
    {
        $this->mapper = $mapper;
        $this->apiKeyForm = $apiKeyForm;
    }

    /**
     * Get all.
     * @return ApiKeyModel[]
     */
    public function findAll()
    {
        return $this->mapper->findAll();
    }

    /**
     * Create an API key.
     * @param array $data
     * @return ApiKeyModel|null
     */
    public function create($data)
    {
        $form = $this->getApiKeyForm();

        $form->bind(new ApiKeyModel());
        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        $key = $form->getData();
        // generate random key
        $key->setSecret(Rand::getString(42));

        $this->mapper->persist($key);
        return $key;
    }

    /**
     * Delete an API key.
     * @param int $id
     */
    public function delete($id)
    {
        $key = $this->mapper->find($id);

        $this->mapper->remove($key);
    }

    /**
     * Get the API key form.
     * @return ApiKeyForm
     */
    public function getApiKeyForm()
    {
        return $this->apiKeyForm;
    }
}
