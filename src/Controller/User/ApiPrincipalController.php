<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Entity\User\ApiPrincipal;
use App\Form\User\ApiPrincipalType;
use App\Service\User\ApiPrincipalService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

use function Symfony\Component\Translation\t;

#[Route(path: '/settings/api-principals')]
final class ApiPrincipalController extends AbstractController
{
    public function __construct(private readonly ApiPrincipalService $apiPrincipalService)
    {
    }

    #[Route(
        path: '',
        name: 'user_api_principal_index',
        methods: ['GET'],
    )]
    public function index(): Response
    {
        return $this->render(
            'user/api-principal/index.html.twig',
            [
                'principals' => $this->apiPrincipalService->findAll(),
            ],
        );
    }

    #[Route(
        path: '/create',
        name: 'user_api_principal_create',
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function create(Request $request): Response
    {
        $principal = new ApiPrincipal();
        $form = $this->createForm(
            ApiPrincipalType::class,
            $principal,
        );
        $form->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {
            $this->apiPrincipalService->create($principal);

            $this->addFlash(
                'success',
                t(
                    'Succesfully created %entity%!',
                    ['%entity%' => t('API principal')],
                ),
            );
            // The only moment the token is readable in full; from here on the principal only hands out a mask.
            $this->addFlash(
                'info',
                t(
                    'Your API token is "%token%". This value will NOT be shown again!',
                    ['%token%' => $principal->getFullToken()],
                ),
            );

            return $this->redirectToRoute('user_api_principal_index');
        }

        return $this->render(
            'user/api-principal/create.html.twig',
            ['form' => $form],
        );
    }

    #[Route(
        path: '/{id}',
        name: 'user_api_principal_edit',
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
        $principal = $this->apiPrincipalService->find($id);

        if (null === $principal) {
            return $this->redirectToIndexAsUnknown();
        }

        $form = $this->createForm(
            ApiPrincipalType::class,
            $principal,
        );
        $form->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {
            $this->apiPrincipalService->save($principal);

            $this->addFlash(
                'success',
                t(
                    'Change(s) of %entity% have been saved!',
                    ['%entity%' => t('API principal')],
                ),
            );

            return $this->redirectToRoute('user_api_principal_index');
        }

        return $this->render(
            'user/api-principal/edit.html.twig',
            ['form' => $form],
        );
    }

    #[Route(
        path: '/delete/{id}',
        name: 'user_api_principal_delete',
        requirements: ['id' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        new Expression("'api_principal_delete-' ~ args['id']"),
        tokenKey: '_csrf_token',
    )]
    public function delete(int $id): Response
    {
        $principal = $this->apiPrincipalService->find($id);

        if (null === $principal) {
            return $this->redirectToIndexAsUnknown();
        }

        $this->apiPrincipalService->remove($principal);

        $this->addFlash(
            'success',
            t(
                'Succesfully deleted %entity%!',
                ['%entity%' => t('API principal')],
            ),
        );

        return $this->redirectToRoute('user_api_principal_index');
    }

    /**
     * A principal that is gone is not worth a 404 page: the overview says so and stays reachable.
     */
    private function redirectToIndexAsUnknown(): RedirectResponse
    {
        $this->addFlash(
            'warning',
            t(
                'Could not find %entity%!',
                ['%entity%' => t('API principal')],
            ),
        );

        return $this->redirectToRoute('user_api_principal_index');
    }
}
