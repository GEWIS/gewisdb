<?php

declare(strict_types=1);

namespace App\Controller\Database;

use App\Entity\Database\Enums\MeetingTypes;
use App\Entity\Database\SubDecision;
use App\Entity\Database\SubDecision\Installation;
use App\Service\Database\Meeting as MeetingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function array_filter;

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
    public function index(): Response
    {
        // The list itself is a live component: it searches and pages without the controller.
        return $this->render('decision/organ/index.html.twig');
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
     * An organ and who is installed in it.
     *
     * An organ is identified by the foundation subdecision that created it, which is why its address is a
     * subdecision's rather than an id of its own.
     */
    #[Route(
        path: '/{type}/{number}/{point}/{decision}/{sequence}',
        name: 'decision_organ_view',
        requirements: [
            'type' => 'ALV|BV|VV|Virt',
            'number' => '-?\d+',
            'point' => '\d+',
            'decision' => '\d+',
            'sequence' => '\d+',
        ],
        methods: ['GET'],
    )]
    public function view(
        MeetingTypes $type,
        int $number,
        int $point,
        int $decision,
        int $sequence,
    ): Response {
        $foundation = $this->meetingService->findFoundation(
            $type,
            $number,
            $point,
            $decision,
            $sequence,
        );

        if (null === $foundation) {
            throw $this->createNotFoundException();
        }

        // A foundation is referenced by discharges and abrogations as well; only installations say who is in it.
        $installations = array_filter(
            $foundation->getReferences()->toArray(),
            static fn (SubDecision $reference): bool => $reference instanceof Installation,
        );

        return $this->render(
            'decision/organ/view.html.twig',
            [
                'foundation' => $foundation,
                'installations' => $installations,
            ],
        );
    }

    #[Route(
        path: '/info/{type}/{number}/{point}/{decision}/{sequence}',
        name: 'decision_organ_info',
        requirements: [
            'type' => 'ALV|BV|VV|Virt',
            'number' => '-?\d+',
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
        $organ = $this->meetingService->getOrganInfo(
            $type,
            $number,
            $point,
            $decision,
            $sequence,
        );

        if (null === $organ) {
            throw $this->createNotFoundException();
        }

        return $this->json($organ);
    }
}
