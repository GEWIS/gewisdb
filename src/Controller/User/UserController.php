<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Entity\User\User;
use App\Form\User\LoginType;
use App\Form\User\UserCreateType;
use App\Form\User\UserEditType;
use App\Service\User\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * The accounts that may use the database, and the two routes through which one gets in and out.
 *
 * The paths are on the actions rather than on the class: logging in is not administration and does not live under the
 * settings prefix.
 */
final class UserController extends AbstractController
{
    public function __construct(private readonly UserService $userService)
    {
    }

    #[Route(
        path: '/settings/user',
        name: 'user_index',
        methods: ['GET'],
    )]
    public function index(): Response
    {
        return $this->render(
            'user/index.html.twig',
            [
                'users' => $this->userService->findAll(),
                'uses_ldap' => $this->userService->usesLdap(),
            ],
        );
    }

    #[Route(
        path: '/settings/user/create',
        name: 'user_create',
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function create(Request $request): Response
    {
        $user = new User();
        $form = $this->createForm(
            UserCreateType::class,
            $user,
        );
        $form->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {
            // The password is not mapped onto the entity: what is stored is a hash of it.
            $this->userService->create(
                $user,
                $form->get('password')->getData(),
            );

            return $this->redirectToRoute('user_index');
        }

        return $this->render(
            'user/create.html.twig',
            ['form' => $form],
        );
    }

    #[Route(
        path: '/settings/user/edit/{id}',
        name: 'user_edit',
        requirements: ['id' => '\d+'],
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function edit(
        Request $request,
        int $id,
    ): Response {
        $user = $this->userService->find($id);

        if (null === $user) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(
            UserEditType::class,
            $user,
        );
        $form->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {
            $this->userService->changePassword(
                $user,
                $form->get('password')->getData(),
            );

            return $this->redirectToRoute('user_index');
        }

        return $this->render(
            'user/edit.html.twig',
            [
                'form' => $form,
                'user' => $user,
            ],
        );
    }

    #[Route(
        path: '/settings/user/delete/{id}',
        name: 'user_delete',
        requirements: ['id' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        new Expression("'user_delete-' ~ args['id']"),
        tokenKey: '_csrf_token',
    )]
    public function delete(int $id): Response
    {
        $user = $this->userService->find($id);

        if (null === $user) {
            throw $this->createNotFoundException();
        }

        $this->userService->remove($user);

        return $this->redirectToRoute('user_index');
    }

    /**
     * Verifying the credentials is the firewall's job; this action only ever renders the form, carrying over what the
     * previous attempt left behind.
     */
    #[Route(
        path: '/login',
        name: 'user_login',
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // The firewall verifies the token under its own id, so the form has to mint it under that id as well.
        $form = $this->createForm(
            LoginType::class,
            ['login' => $authenticationUtils->getLastUsername()],
            ['csrf_token_id' => 'authenticate'],
        );

        $error = $authenticationUtils->getLastAuthenticationError();

        if (null !== $error) {
            $form->addError(new FormError($error->getMessageKey(), null, $error->getMessageData()));
        }

        return $this->render(
            'user/login.html.twig',
            [
                'form' => $form,
                'uses_ldap' => $this->userService->usesLdap(),
            ],
        );
    }

    /**
     * The logout listener handles this route before it ever reaches a controller; the method exists only to give the
     * route something to point at.
     */
    #[Route(
        path: '/login/logout',
        name: 'user_logout',
        methods: ['GET'],
    )]
    public function logout(): void
    {
    }
}
