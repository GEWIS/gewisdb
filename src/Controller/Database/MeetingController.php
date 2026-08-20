<?php

declare(strict_types=1);

namespace App\Controller\Database;

use App\Entity\Database\Enums\MeetingTypes;
use App\Entity\Database\Meeting;
use App\Form\Database\CreateMeetingType;
use App\Service\Database\Meeting as MeetingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/meeting')]
final class MeetingController extends AbstractController
{
    public function __construct(private readonly MeetingService $meetingService)
    {
    }

    #[Route(
        path: '',
        name: 'decision_meeting_index',
        methods: ['GET'],
    )]
    public function index(): Response
    {
        return $this->render('decision/meeting/index.html.twig');
    }

    #[Route(
        path: '/create',
        name: 'decision_meeting_create',
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function create(Request $request): Response
    {
        $meeting = new Meeting();

        $form = $this->createForm(
            CreateMeetingType::class,
            $meeting,
        );
        $form->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {
            if ($this->meetingService->createMeeting($meeting)) {
                return $this->redirectToRoute(
                    'decision_meeting_view',
                    [
                        'type' => $meeting->getType()->value,
                        'number' => $meeting->getNumber(),
                    ],
                );
            }

            // The number is the only part of the meeting that can be corrected: its type and date are what they are.
            $form->get('number')->addError(new FormError('This meeting already exists.'));
        }

        return $this->render(
            'decision/meeting/create.html.twig',
            ['form' => $form],
        );
    }

    /**
     * The meetings a decision can refer to, such as the meeting a set of minutes belongs to.
     */
    #[Route(
        path: '/search',
        name: 'decision_meeting_search',
        methods: ['GET'],
    )]
    public function search(Request $request): JsonResponse
    {
        return $this->json($this->meetingService->searchMeetings((string) $request->query->get('q', '')));
    }

    #[Route(
        path: '/{type}/{number}',
        name: 'decision_meeting_view',
        requirements: [
            'type' => 'ALV|BV|VV|Virt',
            'number' => '-?\d+',
        ],
        methods: ['GET'],
    )]
    public function view(
        MeetingTypes $type,
        int $number,
    ): Response {
        $view = $this->meetingService->getMeetingView(
            $type,
            $number,
        );

        if (null === $view) {
            throw $this->createNotFoundException();
        }

        return $this->render(
            'decision/meeting/view.html.twig',
            [
                'meeting' => $view->meeting,
                'decisions' => $view->decisions,
                'next_decision_numbers' => $view->nextDecisionNumbers,
            ],
        );
    }
}
