<?php

declare(strict_types=1);

namespace User\Controller;

use Laminas\Http\Response as HttpResponse;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use User\Service\UserService;

class SettingsController extends AbstractActionController
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function __construct(
        protected readonly UserService $userService,
        protected readonly array $config,
    ) {
    }

    /**
     * View users.
     */
    public function listUserAction(): ViewModel
    {
        return new ViewModel([
            'users' => $this->userService->findAll(),
            'usesldap' => !empty($this->config['ldap']['basedn']),
        ]);
    }

    /**
     * Create a user.
     */
    public function createUserAction(): HttpResponse|ViewModel
    {
        $form = $this->userService->getCreateForm();

        if ($this->getRequest()->isPost()) {
            $result = $this->userService->create($this->getRequest()->getPost()->toArray());

            if ($result) {
                return $this->redirect()->toRoute('settings/user');
            }
        }

        return new ViewModel(['form' => $form]);
    }

    /**
     * Edit a user.
     */
    public function editUserAction(): HttpResponse|ViewModel
    {
        $form = $this->userService->getEditForm();
        $id = (int) $this->params()->fromRoute('id');
        $user = $this->userService->find($id);

        if (null === $user) {
            return $this->notFoundAction();
        }

        $form->bind($user);

        if ($this->getRequest()->isPost()) {
            $result = $this->userService->edit($user, $this->getRequest()->getPost()->toArray());

            if ($result) {
                return $this->redirect()->toRoute('settings/user');
            }
        }

        return new ViewModel([
            'form' => $form,
            'user' => $user,
        ]);
    }

    /**
     * Remove a user.
     */
    public function removeUserAction(): HttpResponse
    {
        if ($this->getRequest()->isPost()) {
            $this->userService->remove((int) $this->params()->fromRoute('id'));
        }

        return $this->redirect()->toRoute('settings/user');
    }
}
