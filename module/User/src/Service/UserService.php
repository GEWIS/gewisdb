<?php

declare(strict_types=1);

namespace User\Service;

use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Adapter\Ldap as LdapAdapter;
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
    public function __construct(
        protected readonly UserMapper $mapper,
        protected readonly UserCreateForm $createForm,
        protected readonly LoginForm $loginForm,
        protected readonly UserEditForm $editForm,
        protected readonly PasswordInterface $crypt,
        protected readonly AuthenticationService $authService,
        protected readonly array $config,
    ) {
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

        if (!empty($this->config['ldap']['basedn'])) {
            $ldapAdapter = new LdapAdapter($this->getLdapConfig(), $data['login'], $data['password']);
            $result = $this->authService->authenticate($ldapAdapter);
        } else {
            $adapter = $this->authService->getAdapter();
            $adapter->setIdentity($data['login']);
            $adapter->setCredential($data['password']);

            $result = $this->authService->authenticate();
        }

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

    private function getLdapConfig(): array
    {
        return array_map(
            fn ($server) => (
                [
                    'host'                   => $server,
                    'useStartTls'            => $this->config['ldap']['starttls'],
                    'accountDomainName'      => $this->config['ldap']['domain'],
                    'accountFilterFormat'    => $this->config['ldap']['filter'],
                    'username'               => $this->config['ldap']['binduser_username'],
                    'password'               => $this->config['ldap']['binduser_password'],
                    'baseDn'                 => $this->config['ldap']['basedn'],
                    'bindRequiresDn'         => false,
                ]
            ),
            $this->config['ldap']['servers']
        );
    }
}
