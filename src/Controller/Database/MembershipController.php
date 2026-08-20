<?php

declare(strict_types=1);

namespace App\Controller\Database;

use App\Form\Database\MemberExpirationType;
use App\Form\Database\MembershipTypeType;
use App\Form\SubmitButtons;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function Symfony\Component\Translation\t;

/**
 * The membership of one member: which type they hold, and how long it still runs.
 */
#[Route(
    path: '/member/{lidnr}/edit',
    requirements: ['lidnr' => '\d+'],
)]
final class MembershipController extends AbstractMemberController
{
    #[Route(
        path: '/membership',
        name: 'member_membership_edit',
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function membership(
        Request $request,
        int $lidnr,
    ): Response {
        $member = $this->resolveMember($lidnr);

        if ($member instanceof Response) {
            return $member;
        }

        // The last membership rather than the current one: a change is entered against the period that runs latest,
        // and it is that period the change date has to fall inside.
        $membership = $member->getLastMembership();
        $form = $this->createForm(
            MembershipTypeType::class,
            null,
            ['membership' => $membership],
        );
        $form->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {
            $this->memberService->membership(
                $member,
                $form,
            );

            $this->addFlash(
                'success',
                t(
                    'Change(s) of %entity% have been saved!',
                    ['%entity%' => t('membership type')],
                ),
            );

            return $this->redirectToRoute(
                'member_show',
                ['lidnr' => $lidnr],
            );
        }

        return $this->render(
            'member/membership.html.twig',
            [
                'form' => $form,
                'member' => $member,
                'membership' => $membership,
            ],
        );
    }

    #[Route(
        path: '/expiration',
        name: 'member_expiration_edit',
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function expiration(
        Request $request,
        int $lidnr,
    ): Response {
        $member = $this->resolveMember($lidnr);

        if ($member instanceof Response) {
            return $member;
        }

        $form = $this->createForm(MemberExpirationType::class);
        $form->handleRequest($request);

        // `isValid()` before the button: a clicked button says nothing about the token, which is only weighed when
        // the form as a whole is validated.
        if (
            $form->isSubmitted()
            && $form->isValid()
            && SubmitButtons::clicked(
                $form,
                'submit_yes',
            )
        ) {
            $this->memberService->expiration(
                $member,
                $form,
            );

            $this->addFlash(
                'success',
                t(
                    'Change(s) of %entity% have been saved!',
                    ['%entity%' => t('membership expiration date')],
                ),
            );

            return $this->redirectToRoute(
                'member_show',
                ['lidnr' => $lidnr],
            );
        }

        return $this->render(
            'member/expiration.html.twig',
            [
                'form' => $form,
                'member' => $member,
                'new_expiration' => $this->memberService->getExtendedExpiration($member),
            ],
        );
    }
}
