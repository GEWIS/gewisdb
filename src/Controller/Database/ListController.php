<?php

declare(strict_types=1);

namespace App\Controller\Database;

use App\Entity\Database\MailingList;
use App\Form\Database\DeleteListType;
use App\Form\Database\MailingListType;
use App\Service\Database\MailingListService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/settings/lists')]
final class ListController extends AbstractController
{
    public function __construct(private readonly MailingListService $mailingListService)
    {
    }

    #[Route(
        path: '',
        name: 'mailing_list_index',
        methods: ['GET'],
    )]
    public function index(): Response
    {
        return $this->render(
            'mailing/list/index.html.twig',
            [
                'lists' => $this->mailingListService->getAllLists(),
                'listmonk_last_fetch' => $this->mailingListService->getListmonkLastFetch(),
            ],
        );
    }

    #[Route(
        path: '/add',
        name: 'mailing_list_create',
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function create(Request $request): Response
    {
        // The form defaults a list that does not exist yet to being shown on the enrolment form, which it can only
        // do on an instance it is given.
        $list = new MailingList();

        $form = $this->createForm(
            MailingListType::class,
            $list,
            [
                'mailman_lists' => $this->mailingListService->getSelectableMailmanLists(),
                'listmonk_lists' => $this->mailingListService->getSelectableListmonkLists(),
            ],
        );
        $form->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {
            $this->mailingListService->addList($list);
            $this->addFlash(
                'success',
                'The mailing list has been created.',
            );

            return $this->redirectToRoute(
                'mailing_list_edit',
                ['name' => $list->getName()],
            );
        }

        return $this->render(
            'mailing/list/form.html.twig',
            [
                'form' => $form,
                'list' => null,
            ],
        );
    }

    #[Route(
        path: '/edit/{name}',
        name: 'mailing_list_edit',
        requirements: ['name' => '[a-zA-Z0-9_-]+'],
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function edit(
        Request $request,
        string $name,
    ): Response {
        $list = $this->mailingListService->getList($name);

        if (null === $list) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(
            MailingListType::class,
            $list,
            [
                'mailman_lists' => $this->mailingListService->getSelectableMailmanLists($list),
                'listmonk_lists' => $this->mailingListService->getSelectableListmonkLists($list),
            ],
        );
        $form->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {
            $this->mailingListService->editList($list);
            $this->addFlash(
                'success',
                'The mailing list has been updated.',
            );

            return $this->redirectToRoute(
                'mailing_list_edit',
                ['name' => $list->getName()],
            );
        }

        return $this->render(
            'mailing/list/form.html.twig',
            [
                'form' => $form,
                'list' => $list,
            ],
        );
    }

    #[Route(
        path: '/delete/{name}',
        name: 'mailing_list_delete',
        requirements: ['name' => '[a-zA-Z0-9_-]+'],
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function delete(
        Request $request,
        string $name,
    ): Response {
        $list = $this->mailingListService->getList($name);

        if (null === $list) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(DeleteListType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // The form is only valid when the confirming button was the one clicked, so a declined confirmation
            // simply returns to the overview.
            if ($form->isValid()) {
                $this->mailingListService->delete($list);
                $this->addFlash(
                    'success',
                    'The mailing list has been deleted.',
                );
            }

            return $this->redirectToRoute('mailing_list_index');
        }

        return $this->render(
            'mailing/list/delete.html.twig',
            [
                'form' => $form,
                'name' => $name,
            ],
        );
    }
}
