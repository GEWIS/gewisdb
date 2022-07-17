<?php

namespace User\Service;

use Laminas\Authentication\AuthenticationService;
use Laminas\Crypt\Password\PasswordInterface;
use User\Mapper\UserMapper;
use User\Model\User as UserModel;
use User\Form\{
    UserCreate as UserCreateForm,
    Login as LoginForm,
    UserEdit as UserEditForm,
};

class UserService
{
    protected UserMapper $mapper;

    protected UserCreateForm $createForm;

    protected UserEditForm $editForm;

    protected LoginForm $loginForm;

    protected PasswordInterface $crypt;

    protected AuthenticationService $authService;

    public function __construct(
        UserMapper $mapper,
        UserCreateForm $createForm,
        LoginForm $loginForm,
        UserEditForm $editForm,
        PasswordInterface $crypt,
        AuthenticationService $authService,
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
     */
    public function create(array $data): bool
    {
        $form = $this->getCreateForm();

        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $data = $form->getData();
        $password = $this->crypt->create($data['password']);

        $user = new UserModel();
        $user->setLogin($data['login']);
        $user->setPassword($password);

        $this->mapper->persist($user);

        return true;
    }

    /**
     * Edit a user
     */
    public function edit(
        UserModel $user,
        array $data,
    ): bool {
        $form = $this->getEditForm();

        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $data = $form->getData();
        $password = $this->crypt->create($data['password']);

        $user->setPassword($password);

        $this->mapper->persist($user);

        return true;
    }

    /**
     * Remove a user
     */
    public function remove(int $id): void
    {
        if (null !== ($user = $this->find($id))) {
            $this->mapper->remove($user);
        }
    }

    /**
     * Log a user in.
     */
    public function login(array $data): bool
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
    public function logout(): void
    {
        $this->authService->clearIdentity();
    }

    /**
     * Get all users.
     *
     * @return array<array-key, UserModel>
     */
    public function findAll(): array
    {
        return $this->mapper->findAll();
    }

    /**
     * Get a User by ID.
     */
    public function find(int $id): ?UserModel
    {
        return $this->mapper->find($id);
    }

    /**
     * Get the create form.
     */
    public function getCreateForm(): UserCreateForm
    {
        return $this->createForm;
    }

    /**
     * Get the edit form.
     */
    public function getEditForm(): UserEditForm
    {
        return $this->editForm;
    }

    /**
     * Get the login form.
     */
    public function getLoginForm(): LoginForm
    {
        return $this->loginForm;
    }
}
