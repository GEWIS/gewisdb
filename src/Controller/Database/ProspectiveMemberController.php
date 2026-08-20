<?php

declare(strict_types=1);

namespace App\Controller\Database;

use App\Entity\Database\Enums\MembershipTypes;
use App\Entity\Database\ProspectiveMember as ProspectiveMemberModel;
use App\Form\Database\MemberApproveType;
use App\Form\Database\MemberRenewalType;
use App\Form\Database\RegistrationType;
use App\Service\Database\Member as MemberService;
use App\Service\Database\ProspectiveMemberRemoval;
use App\Service\Database\RegistrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_map;

/**
 * Everyone who has registered but whose membership the secretary has not confirmed yet, from the public sign-up form
 * that creates them to the moment they become a member or are removed again.
 *
 * The paths are on the actions rather than on the class: the sign-up form is served from the join vhost and does not
 * live under the administrative prefix.
 */
final class ProspectiveMemberController extends AbstractController
{
    public function __construct(
        private readonly MemberService $memberService,
        private readonly RegistrationService $registrationService,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * The public sign-up form, filled in by someone who is not (yet) anyone to us.
     */
    #[Route(
        path: '/member/subscribe',
        name: 'join_subscribe_index',
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function subscribe(Request $request): Response
    {
        if (!$this->registrationService->isOpen($request->getClientIp())) {
            return $this->render('join/subscribe-disabled.html.twig');
        }

        $form = $this->createForm(
            RegistrationType::class,
            null,
            [
                'mailing_lists' => $this->registrationService->getMailingListsOnForm(),
            ],
        );
        $form->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {
            $checkoutUrl = $this->registrationService->register($form);

            if (null !== $checkoutUrl) {
                // Rendered rather than answered with a 303, because the Chromium CSP enforcer does not allow a
                // redirect after a POST.
                return $this->render(
                    'application/redirect.html.twig',
                    [
                        'destination' => $this->translator->trans('our payment provider'),
                        'url' => $checkoutUrl,
                    ],
                );
            }

            // A registration that is rejected leaves its reason on the form; one that is still valid was stored and
            // only lacks a checkout page, which the e-mail that just went out can restart.
            if ($form->isValid()) {
                return $this->redirectToRoute('join_checkout_error');
            }
        }

        return $this->render(
            'join/subscribe.html.twig',
            ['form' => $form],
        );
    }

    /**
     * Graduate renewal, reached from the link in the renewal e-mail.
     *
     * Served from the join host and open to anyone holding the token: whoever follows the link is not signed in, and
     * the token is what says who they are. A token that has been used or has expired is not an error — the page says
     * the link no longer works rather than pretending it does.
     */
    #[Route(
        path: '/renew/{token}',
        name: 'join_renew_short',
        requirements: ['token' => '[a-zA-Z0-9_\-\+]+'],
        methods: [
            'GET',
            'POST',
        ],
    )]
    #[Route(
        path: '/member/renew/{token}',
        name: 'join_renew',
        requirements: ['token' => '[a-zA-Z0-9_\-\+]+'],
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function renew(
        Request $request,
        string $token,
    ): Response {
        $renewalLink = $this->memberService->getRenewalLink($token);

        if (null === $renewalLink) {
            return $this->render('join/renew-unavailable.html.twig');
        }

        $member = $renewalLink->getMember();
        $form = $this->createForm(
            MemberRenewalType::class,
            $member,
            ['renewal_link' => $renewalLink],
        );
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $email = (string) $form->get('email')->getData();

            if (
                $this->memberService->emailBelongsToSomeoneElse(
                    $email,
                    $member,
                )
            ) {
                $form->get('email')->addError(new FormError(
                    $this->translator->trans('There already is a member with this e-mail address.'),
                ));
            } elseif ($form->isValid()) {
                $this->memberService->renewMember(
                    $member,
                    $renewalLink,
                    $renewalLink->getNewExpiration(),
                );

                return $this->render(
                    'join/renew-done.html.twig',
                    ['member' => $member],
                );
            }
        }

        return $this->render(
            'join/renew.html.twig',
            ['form' => $form],
        );
    }

    #[Route(
        path: '/prospective-member',
        name: 'join_prospective_member_index',
        methods: ['GET'],
    )]
    public function index(): Response
    {
        return $this->render('join/prospective-member/index.html.twig');
    }

    /**
     * The overview searches while typing; `type` picks which of its three tables is being filled.
     */
    #[Route(
        path: '/prospective-member/search',
        name: 'join_prospective_member_search',
        methods: ['GET'],
    )]
    public function search(Request $request): JsonResponse
    {
        $prospectiveMembers = $this->memberService->searchProspective(
            (string) $request->query->get(
                'q',
                '',
            ),
            (string) $request->query->get(
                'type',
                '',
            ),
        );

        return $this->json(array_map(
            fn (ProspectiveMemberModel $prospectiveMember): array => [
                'lidnr' => $prospectiveMember->getLidnr(),
                'fullName' => $prospectiveMember->getFullName(),
                'email' => $prospectiveMember->getEmail(),
                'url' => $this->generateUrl(
                    'join_prospective_member_show',
                    ['id' => $prospectiveMember->getLidnr()],
                ),
            ],
            $prospectiveMembers,
        ));
    }

    #[Route(
        path: '/prospective-member/{id}',
        name: 'join_prospective_member_show',
        requirements: ['id' => '\d+'],
        methods: ['GET'],
    )]
    public function show(int $id): Response
    {
        $prospectiveMember = $this->memberService->getProspectiveMember($id);

        if (null === $prospectiveMember['member']) {
            throw $this->createNotFoundException();
        }

        return $this->render(
            'join/prospective-member/show.html.twig',
            [
                'member' => $prospectiveMember['member'],
                'canDelete' => $prospectiveMember['canDelete'],
                'approveMessages' => $prospectiveMember['approveMessages'],
                // Only a prospective member whose payment is settled can be approved, so the rest do not get a form.
                'form' => true === $prospectiveMember['canBeApproved']
                    ? $this->createForm(MemberApproveType::class)
                    : null,
            ],
        );
    }

    #[Route(
        path: '/prospective-member/{id}/finalize',
        name: 'join_prospective_member_finalize',
        requirements: ['id' => '\d+'],
        methods: ['POST'],
    )]
    public function finalize(
        Request $request,
        int $id,
    ): Response {
        $prospectiveMember = $this->memberService->getProspectiveMember($id)['member'];

        if (null === $prospectiveMember) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(MemberApproveType::class);
        $form->handleRequest($request);
        $membershipType = $form->get('type')->getData();

        if (
            $form->isSubmitted()
            && $form->isValid()
            && $membershipType instanceof MembershipTypes
        ) {
            $member = $this->memberService->finalizeSubscription(
                $membershipType,
                $prospectiveMember,
            );

            if (null !== $member) {
                $this->addFlash(
                    'success',
                    'The membership has been confirmed.',
                );

                return $this->redirectToRoute(
                    'member_show',
                    ['lidnr' => $member->getLidnr()],
                );
            }
        }

        $this->addFlash(
            'danger',
            'This prospective member cannot be approved.',
        );

        return $this->redirectToRoute(
            'join_prospective_member_show',
            ['id' => $id],
        );
    }

    #[Route(
        path: '/prospective-member/{id}/delete',
        name: 'join_prospective_member_delete',
        requirements: ['id' => '\d+'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        new Expression("'prospective_member_delete-' ~ args['id']"),
        tokenKey: '_csrf_token',
    )]
    public function delete(int $id): Response
    {
        $prospectiveMember = $this->memberService->getProspectiveMember($id)['member'];

        if (null === $prospectiveMember) {
            throw $this->createNotFoundException();
        }

        $removal = $this->registrationService->removeProspectiveMember($prospectiveMember);

        if (ProspectiveMemberRemoval::Removed === $removal) {
            $this->addFlash(
                'success',
                'The prospective member has been removed.',
            );

            return $this->redirectToRoute('join_prospective_member_index');
        }

        if (ProspectiveMemberRemoval::NotRemovable === $removal) {
            return $this->redirectToRoute(
                'join_prospective_member_show',
                ['id' => $id],
            );
        }

        // The membership fee is still with us, so the prospective member stays on file until the refund is settled.
        return $this->render(
            'join/prospective-member/delete.html.twig',
            ['reason' => $removal],
        );
    }
}
