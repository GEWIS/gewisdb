<?php

declare(strict_types=1);

namespace App\Controller\Database;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

use function Symfony\Component\Translation\t;

/**
 * The changes members proposed to their own data, which only take effect once the secretary approves them.
 */
#[Route(path: '/member')]
final class MemberUpdateController extends AbstractMemberController
{
    #[Route(
        path: '/updates',
        name: 'member_update_index',
        methods: ['GET'],
    )]
    public function index(): Response
    {
        return $this->render(
            'member/updates.html.twig',
            [
                'updates' => $this->memberService->getPendingMemberUpdates(),
            ],
        );
    }

    #[Route(
        path: '/{lidnr}/update',
        name: 'member_update_show',
        requirements: ['lidnr' => '\d+'],
        methods: ['GET'],
    )]
    public function show(int $lidnr): Response
    {
        $memberUpdate = $this->memberService->getPendingMemberUpdate($lidnr);

        if (null === $memberUpdate) {
            throw $this->createNotFoundException();
        }

        $member = $this->resolveMember($lidnr);

        if ($member instanceof Response) {
            return $member;
        }

        return $this->render(
            'member/show-update.html.twig',
            [
                'member' => $member,
                'member_update' => $memberUpdate,
            ],
        );
    }

    #[Route(
        path: '/{lidnr}/update/approve',
        name: 'member_update_approve',
        requirements: ['lidnr' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        new Expression("'member_update-' ~ args['lidnr']"),
        tokenKey: '_csrf_token',
    )]
    public function approve(int $lidnr): Response
    {
        $memberUpdate = $this->memberService->getPendingMemberUpdate($lidnr);

        if (null === $memberUpdate) {
            throw $this->createNotFoundException();
        }

        $member = $this->resolveMember($lidnr);

        if ($member instanceof Response) {
            return $member;
        }

        $this->memberService->approveMemberUpdate(
            $member,
            $memberUpdate,
        );

        $this->addFlash(
            'success',
            t(
                'Change(s) of %entity% have been approved and saved!',
                ['%entity%' => t('member')],
            ),
        );

        return $this->redirectToRoute('member_update_index');
    }

    #[Route(
        path: '/{lidnr}/update/reject',
        name: 'member_update_reject',
        requirements: ['lidnr' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        new Expression("'member_update-' ~ args['lidnr']"),
        tokenKey: '_csrf_token',
    )]
    public function reject(int $lidnr): Response
    {
        $memberUpdate = $this->memberService->getPendingMemberUpdate($lidnr);

        if (null === $memberUpdate) {
            throw $this->createNotFoundException();
        }

        $this->memberService->rejectMemberUpdate($memberUpdate);

        $this->addFlash(
            'info',
            t(
                'Change(s) of %entity% have been rejected!',
                ['%entity%' => t('member')],
            ),
        );

        return $this->redirectToRoute('member_update_index');
    }
}
