<?php

declare(strict_types=1);

namespace User\Service;

use Laminas\Form\FormInterface;
use User\Form\ApiPrincipal as ApiPrincipalForm;
use User\Mapper\ApiPrincipalMapper;
use User\Model\ApiPrincipal as ApiPrincipalModel;
use User\Model\Enums\ApiPermissions;

use function array_map;

class ApiPrincipalService
{
    public function __construct(
        protected readonly ApiPrincipalForm $apiPrincipalForm,
        protected readonly ApiPrincipalMapper $apiPrincipalMapper,
    ) {
    }

    public function getCreateForm(): ApiPrincipalForm
    {
        return $this->apiPrincipalForm;
    }

    public function getEditForm(ApiPrincipalModel $principal): ApiPrincipalForm
    {
        $this->apiPrincipalForm->bind($principal);

        return $this->apiPrincipalForm;
    }

    /**
     * @param array<array-key,mixed> $data
     */
    public function create(array $data): false|ApiPrincipalModel
    {
        $form = $this->getCreateForm();
        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $data = $form->getData(FormInterface::VALUES_AS_ARRAY);

        $principal = new ApiPrincipalModel();
        $principal->setDescription($data['description']);
        $principal->generateToken();

        $permissions = array_map(
            static function ($p): ApiPermissions {
                return ApiPermissions::from($p);
            },
            $data['permissions'],
        );
        $principal->setPermissions($permissions);

        $this->apiPrincipalMapper->persist($principal);

        return $principal;
    }

    /**
     * @param array<array-key,mixed> $data
     */
    public function edit(
        ApiPrincipalModel $principal,
        array $data,
    ): bool {
        $form = $this->getEditForm($principal);

        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $this->apiPrincipalMapper->persist($principal);

        return true;
    }

    /**
     * Remove a principal
     */
    public function remove(int $id): bool
    {
        if (null === ($principal = $this->find($id))) {
            return false;
        }

        $this->apiPrincipalMapper->remove($principal);

        return true;
    }

    /**
     * Get all Api Principals.
     *
     * @return ApiPrincipalModel[]
     */
    public function findAll(): array
    {
        return $this->apiPrincipalMapper->findAll();
    }

    /**
     * Get an API principal by ID
     */
    public function find(int $id): ?ApiPrincipalModel
    {
        return $this->apiPrincipalMapper->find($id);
    }
}
