<?php

declare(strict_types=1);

namespace App\Controller\Decision;

use App\Entity\Decision\Enums\MeetingTypes;
use App\Service\Decision\Meeting as MeetingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/organ')]
final class OrganController extends AbstractController
{
    public function __construct(private readonly MeetingService $meetingService)
    {
    }

    #[Route(
        path: '',
        name: 'decision_organ_index',
        methods: ['GET'],
    )]
    public function index(Request $request): Response
    {
        $query = (string) $request->query->get('q', '');

        return $this->render('decision/organ/index.html.twig', [
            'query' => $query,
            'organs' => $this->meetingService->findOrgans($query),
        ]);
    }

    /**
     * The organs a decision can be taken about.
     */
    #[Route(
        path: '/search',
        name: 'decision_organ_search',
        methods: ['GET'],
    )]
    public function search(Request $request): JsonResponse
    {
        return $this->json($this->meetingService->searchOrgans((string) $request->query->get('q', '')));
    }

    /**
     * One organ, with everyone currently installed in it.
     */
    #[Route(
        path: '/info/{type}/{number}/{point}/{decision}/{sequence}',
        name: 'decision_organ_info',
        requirements: [
            'type' => 'ALV|BV|VV|Virt',
            'number' => '\d+',
            'point' => '\d+',
            'decision' => '\d+',
            'sequence' => '\d+',
        ],
        methods: ['GET'],
    )]
    public function info(
        MeetingTypes $type,
        int $number,
        int $point,
        int $decision,
        int $sequence,
    ): JsonResponse {
        $organ = $this->meetingService->getOrganInfo($type, $number, $point, $decision, $sequence);

        if (null === $organ) {
            throw $this->createNotFoundException();
        }

        return $this->json($organ);
    }
}
