<?php

namespace User\Service;

use User\Mapper\UserMapper;
use User\Form\UserCreate;
use User\Model\User;
use Zend\Crypt\Password\PasswordInterface;

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
     * @var PasswordInterface
     */
    protected $crypt;


    /**
     * @param UserMapper $mapper
     * @return bool
     */
    public function __construct(
        UserMapper $mapper,
        UserCreate $createForm,
        PasswordInterface $crypt
    ) {
        $this->mapper = $mapper;
        $this->createForm = $createForm;
        $this->crypt = $crypt;
    }

    /**
     * Create a user.
     * @param array $data
     */
    public function create($data)
    {
        $form = $this->getCreateForm();

        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $data = $form->getData();
        $password = $this->crypt->create($data['password']);

        $user = new User();
        $user->setLogin($data['login']);
        $user->setPassword($password);

        $this->mapper->persist($user);

        return true;
    }

    /**
     * Get all users.
     * @return User[]
     */
    public function findAll()
    {
        return $this->mapper->findAll();
    }

    /**
     * Get the create form.
     * @return UserCreate
     */
    public function getCreateForm()
    {
        return $this->createForm;
    }
}
