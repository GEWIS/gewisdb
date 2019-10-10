<?php

namespace User\Service;

use User\Mapper\UserMapper;
use User\Form\UserCreate;

class UserService
{

    /**
     * @var UserMapper
     */
    protected $mapper;

    /**
     * @var UserCreate
     */
    protected $createForm;


    /**
     * @param UserMapper $mapper
     */
    public function __construct(UserMapper $mapper, UserCreate $createForm)
    {
        $this->mapper = $mapper;
        $this->createForm = $createForm;
    }

    /**
     * Get the create form.
     *
     * @return UserCreate
     */
    public function getCreateForm()
    {
        return $this->createForm;
    }
}
