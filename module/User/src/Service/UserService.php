<?php

namespace User\Service;

use User\Mapper\UserMapper;
use User\Form\UserCreate;
use User\Model\User;
use Zend\Crypt\Password\PasswordInterface;
use User\Form\Login;
use Zend\Authentication\AuthenticationService;
use User\Form\UserEdit;

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
     * @var UserEdit
     */
    protected $editForm;

    /**
     * @var Login
     */
    protected $loginForm;

    /**
     * @var PasswordInterface
     */
    protected $crypt;

    /**
     * @var AuthenticationService
     */
    protected $authService;


    /**
     * @param UserMapper $mapper
     * @return bool
     */
    public function __construct(
        UserMapper $mapper,
        UserCreate $createForm,
        Login $loginForm,
        UserEdit $editForm,
        PasswordInterface $crypt,
        AuthenticationService $authService
    ) {
        $this->mapper = $mapper;
        $this->createForm = $createForm;
        $this->loginForm = $loginForm;
        $this->editForm = $editForm;
        $this->crypt = $crypt;
        $this->authService = $authService;
    }

    /**
     * Create a user.
     * @param array $data
     * @return bool
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
     * Log a user in.
     * @param array $data
     * @return bool
     */
    public function login($data)
    {
        $form = $this->getLoginForm();

        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $data = $form->getData();

        $adapter = $this->authService->getAdapter();
        $adapter->setIdentity($data['login']);
        $adapter->setCredential($data['password']);

        $result = $this->authService->authenticate();

        return $result->isValid();
    }

    /**
     * Log a user out.
     */
    public function logout()
    {
        $this->authService->clearIdentity();
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
     * Get a User by ID.
     * @param int $id
     * @return User
     */
    public function find($id)
    {
        return $this->mapper->find($id);
    }

    /**
     * Get the create form.
     * @return UserCreate
     */
    public function getCreateForm()
    {
        return $this->createForm;
    }

    /**
     * Get the edit form.
     * @return UserEdit
     */
    public function getEditForm()
    {
        return $this->editForm;
    }

    /**
     * Get the login form.
     * @return Login
     */
    public function getLoginForm()
    {
        return $this->loginForm;
    }
}
