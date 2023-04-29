<?php

declare(strict_types=1);

namespace User\Service;

use User\Form\ApiPrincipal as ApiPrincipalForm;
use User\Mapper\ApiPrincipalMapper;
use User\Model\ApiPrincipal as ApiPrincipalModel;
use User\Model\Enums\ApiPermissions;

use function array_map;

class ApiPrincipalService
{
    public function __construct(
        protected readonly ApiPrincipalMapper $mapper,
        protected readonly ApiPrincipalForm $apiPrincipalForm,
    ) {
    }

    public function getCreateForm(): ApiPrincipalForm
    {
        return $this->apiPrincipalForm;
    }

    /**
     * @param array<array-key,string> $data
     */
    public function create(array $data): bool
    {
        $form = $this->getCreateForm();
        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $data = $form->getData();

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

        $this->mapper->persist($principal);

        return true;
    }

    /**
     * Get all Api Principals.
     *
     * @return ApiPrincipalModel[]
     */
    public function findAll(): array
    {
        return $this->mapper->findAll();
    }
}
