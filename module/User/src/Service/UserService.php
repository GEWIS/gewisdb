<?php

namespace User\Service;

use User\Mapper\UserMapper;

class UserService
{

    /**
     * @var UserMapper
     */
    protected $mapper;


    /**
     * @param UserMapper $mapper
     */
    public function __construct(UserMapper $mapper)
    {
        $this->mapper = $mapper;
    }
}
