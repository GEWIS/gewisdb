<?php

declare(strict_types=1);

namespace User\Controller;

use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use User\Service\UserService;

class SettingsController extends AbstractActionController
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function __construct(
        protected readonly UserService $service,
        protected readonly array $config,
    ) {
    }

    /**
     * View users.
     */
    public function indexAction(): ViewModel
    {
        return new ViewModel([
            'users' => $this->service->findAll(),
            'usesldap' => !empty($this->config['ldap']['basedn']),
        ]);
    }

    /**
     * Create a user.
     */
    public function createAction(): Response|ViewModel
    {
        $form = $this->service->getCreateForm();

        if ($this->getRequest()->isPost()) {
            $result = $this->service->create($this->getRequest()->getPost()->toArray());

            if ($result) {
                return $this->redirect()->toRoute('settings/user');
            }
        }

        return new ViewModel(['form' => $form]);
    }

    /**
     * Edit a user.
     */
    public function editAction(): Response|ViewModel
    {
        $form = $this->service->getEditForm();
        $id = (int) $this->params()->fromRoute('id');
        $user = $this->service->find($id);

        if (null === $user) {
            return $this->notFoundAction();
        }

        if ($this->getRequest()->isPost()) {
            $result = $this->service->edit($user, $this->getRequest()->getPost()->toArray());

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
    public function removeAction(): Response
    {
        if ($this->getRequest()->isPost()) {
            $this->service->remove((int) $this->params()->fromRoute('id'));
        }

        return $this->redirect()->toRoute('settings/user');
    }
}
