<?php

declare(strict_types=1);

namespace App\Controller\Database;

use App\Entity\Database\Enums\MembershipTypes;
use App\Entity\Database\Member;
use App\Form\Database\AuditNoteType;
use App\Form\Database\BulkMemberRenewalType;
use App\Form\Database\DeleteMemberType;
use App\Form\Database\MemberEditType;
use App\Form\Database\MemberListsType;
use App\Form\SubmitButtons;
use App\Service\Database\Member as MemberService;
use App\ViewModel\Database\MemberProfile;
use App\ViewModel\Database\MemberSearchResult;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_map;
use function Symfony\Component\Translation\t;

/**
 * Members: the overview and the searches behind it, the record of one member, and everything that acts on that record
 * without being an entity of its own -- the Supremum opt-in, the mailing list subscriptions, and the batch of members
 * that needs attention.
 */
#[Route(path: '/member')]
final class MemberController extends AbstractMemberController
{
    /**
     * How far ahead an expiring membership is worth acting on.
     */
    private const int EXPIRES_WITHIN_DAYS = 30;

    public function __construct(
        MemberService $memberService,
        private readonly TranslatorInterface $translator,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
        parent::__construct($memberService);
    }

    /**
     * The overview is a table the browser fills in while the secretary types, so the page itself is static and the two
     * searches below answer in JSON.
     */
    #[Route(
        path: '',
        name: 'member_index',
        methods: ['GET'],
    )]
    public function index(): Response
    {
        return $this->render('member/index.html.twig');
    }

    #[Route(
        path: '/search',
        name: 'member_search',
        methods: ['GET'],
    )]
    public function search(Request $request): JsonResponse
    {
        return $this->json($this->results($this->memberService->search($request->query->getString('q'))));
    }

    /**
     * The members that can still be picked for a decision.
     */
    #[Route(
        path: '/searchFiltered',
        name: 'member_search_filtered',
        methods: ['GET'],
    )]
    public function searchFiltered(Request $request): JsonResponse
    {
        return $this->json($this->results($this->memberService->searchFiltered($request->query->getString('q'))));
    }

    /**
     * The members whose membership is about to run out or whose record is incomplete, and what to do about each.
     */
    #[Route(
        path: '/attention-needed',
        name: 'member_attention_index',
        methods: ['GET'],
    )]
    public function attention(): Response
    {
        return $this->render(
            'member/attention-needed.html.twig',
            $this->memberService->getMembersRequiringAttention(self::EXPIRES_WITHIN_DAYS),
        );
    }

    /**
     * Renewing a batch of memberships at once.
     *
     * The page is a preview first and a change second: the numbers and the type are checked, and only the confirm
     * button carries them out. The shortcuts on the attention overview link here with both already filled in, which
     * previews them without a submission of its own.
     */
    #[Route(
        path: '/bulk-renewal',
        name: 'member_bulk_renewal_index',
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function bulkRenewal(Request $request): Response
    {
        $form = $this->createForm(
            BulkMemberRenewalType::class,
            [
                'memberIds' => $request->query->getString('memberIds'),
                'membershipType' => MembershipTypes::tryFrom($request->query->getString('membershipType')),
            ],
        );
        $form->handleRequest($request);

        // Numbers that did not survive the form are not worth previewing; the errors on the fields say why.
        $data = $form->isSubmitted() && !$form->isValid()
            ? null
            : $form->getData();

        return $this->render(
            'member/bulk-renewal.html.twig',
            [
                'form' => $form,
                ...$this->memberService->bulkRenewal(
                    $data['memberIds'] ?? '',
                    $data['membershipType'] ?? null,
                    SubmitButtons::clicked(
                        $form,
                        'intent',
                    ),
                ),
            ],
        );
    }

    #[Route(
        path: '/{lidnr}',
        name: 'member_show',
        requirements: ['lidnr' => '\d+'],
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function show(
        Request $request,
        int $lidnr,
    ): Response {
        $member = $this->memberService->getMemberWithDecisions($lidnr);
        // The query that works out the organ memberships only answers for a member who has any that hold up, so a
        // member it does not answer for is looked up plainly and their organs are not stated at all.
        $hasCorrectInstallations = null !== $member;

        if (null === $member) {
            $member = $this->memberService->getMember($lidnr);

            if (null === $member) {
                throw $this->createNotFoundException();
            }
        }

        $guarded = $this->guardDeleted($member);

        if ($guarded instanceof Response) {
            return $guarded;
        }

        $noteForm = $this->createForm(AuditNoteType::class);
        $noteForm->handleRequest($request);

        if (
            $noteForm->isSubmitted()
            && $noteForm->isValid()
        ) {
            $this->memberService->addAuditNote(
                $member,
                $noteForm,
            );

            $this->addFlash(
                'success',
                t(
                    '%entity% has been added to %target%',
                    [
                        '%entity%' => t('Note'),
                        '%target%' => t('member'),
                    ],
                ),
            );

            return $this->redirectToRoute(
                'member_show',
                ['lidnr' => $lidnr],
            );
        }

        $profile = MemberProfile::fromMember(
            $member,
            $hasCorrectInstallations,
            $this->translator,
            $this->urlGenerator,
        );

        return $this->render(
            'member/show.html.twig',
            [
                'member' => $profile->member,
                'has_correct_installations' => $profile->hasCorrectInstallations,
                'membership_ends_on' => $profile->membershipEndsOn,
                'can_change_membership_type' => $profile->canChangeMembershipType,
                'can_extend' => $profile->canExtend,
                'organs' => $profile->organs,
                'notes' => $profile->notes,
                'note_form' => $noteForm,
            ],
        );
    }

    #[Route(
        path: '/{lidnr}/edit',
        name: 'member_edit',
        requirements: ['lidnr' => '\d+'],
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function edit(
        Request $request,
        int $lidnr,
    ): Response {
        $member = $this->resolveMember($lidnr);

        if ($member instanceof Response) {
            return $member;
        }

        $form = $this->createForm(
            MemberEditType::class,
            $member,
        );
        $form->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {
            $this->memberService->edit(
                $member,
                $form,
            );

            $this->addFlash(
                'success',
                t(
                    'Change(s) of %entity% have been saved!',
                    ['%entity%' => t('member')],
                ),
            );

            return $this->redirectToRoute(
                'member_show',
                ['lidnr' => $lidnr],
            );
        }

        return $this->render(
            'member/edit.html.twig',
            [
                'form' => $form,
                'member' => $member,
            ],
        );
    }

    /**
     * The confirmation and the removal are one route, so the confirmation is a form: it is the form that carries the
     * token, which a bare POST on a page that is also reachable by GET could not.
     */
    #[Route(
        path: '/{lidnr}/delete',
        name: 'member_delete',
        requirements: ['lidnr' => '\d+'],
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function delete(
        Request $request,
        int $lidnr,
    ): Response {
        $member = $this->resolveMember($lidnr);

        if ($member instanceof Response) {
            return $member;
        }

        $form = $this->createForm(DeleteMemberType::class);
        $form->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {
            if (
                !SubmitButtons::clicked(
                    $form,
                    'submit_yes',
                )
            ) {
                return $this->redirectToRoute(
                    'member_show',
                    ['lidnr' => $lidnr],
                );
            }

            $this->memberService->remove($member);

            $this->addFlash(
                'success',
                t(
                    'Succesfully deleted %entity%!',
                    ['%entity%' => t('member')],
                ),
            );

            return $this->redirectToRoute('member_index');
        }

        return $this->render(
            'member/delete.html.twig',
            [
                'form' => $form,
                'member' => $member,
                // A member who is named in a decision, budget or statement cannot go entirely; the confirmation
                // says so.
                'can_remove' => $this->memberService->canRemove($member),
            ],
        );
    }

    /**
     * Whether a member receives the Supremum. The three answers -- yes, no, and "not stated" -- are three routes
     * rather than a value in the path, so that the member page can post to each of them directly.
     */
    #[Route(
        path: '/{lidnr}/supremum',
        name: 'member_supremum_reset',
        requirements: ['lidnr' => '\d+'],
        defaults: ['value' => ''],
        methods: ['POST'],
    )]
    #[Route(
        path: '/{lidnr}/supremum/optin',
        name: 'member_supremum_optin',
        requirements: ['lidnr' => '\d+'],
        defaults: ['value' => 'optin'],
        methods: ['POST'],
    )]
    #[Route(
        path: '/{lidnr}/supremum/optout',
        name: 'member_supremum_optout',
        requirements: ['lidnr' => '\d+'],
        defaults: ['value' => 'optout'],
        methods: ['POST'],
    )]
    #[IsCsrfTokenValid(
        new Expression("'member_supremum-' ~ args['lidnr']"),
        tokenKey: '_csrf_token',
    )]
    public function supremum(
        int $lidnr,
        string $value,
    ): Response {
        $member = $this->resolveMember($lidnr);

        if ($member instanceof Response) {
            return $member;
        }

        $this->memberService->setSupremum(
            $member,
            $value,
        );

        return $this->redirectToRoute(
            'member_show',
            ['lidnr' => $lidnr],
        );
    }

    /**
     * The mailing lists one member is subscribed to.
     */
    #[Route(
        path: '/{lidnr}/edit/lists',
        name: 'member_lists_edit',
        requirements: ['lidnr' => '\d+'],
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function lists(
        Request $request,
        int $lidnr,
    ): Response {
        $member = $this->resolveMember($lidnr);

        if ($member instanceof Response) {
            return $member;
        }

        if ($this->memberService->isMailingListSyncLocked()) {
            return $this->render(
                'member/mailinglist-sync-status.html.twig',
                ['member' => $member],
            );
        }

        $form = $this->createForm(
            MemberListsType::class,
            null,
            ['member' => $member],
        );
        $form->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {
            // A sync that started while the page was open takes precedence, and the subscriptions are left alone.
            if (
                null !== $this->memberService->subscribeLists(
                    $member,
                    $form,
                )
            ) {
                $this->addFlash(
                    'success',
                    t(
                        'Change(s) of %entity% have been saved!',
                        ['%entity%' => t('mailing list subscriptions')],
                    ),
                );

                return $this->redirectToRoute(
                    'member_show',
                    ['lidnr' => $lidnr],
                );
            }

            $this->addFlash(
                'danger',
                t(
                    'Could not save change(s) of %entity%!',
                    ['%entity%' => t('mailing list subscriptions')],
                ),
            );
        }

        return $this->render(
            'member/lists.html.twig',
            [
                'form' => $form,
                'member' => $member,
            ],
        );
    }

    /**
     * @param Member[] $members
     *
     * @return MemberSearchResult[]
     */
    private function results(array $members): array
    {
        return array_map(
            fn (Member $member): MemberSearchResult => MemberSearchResult::fromMember(
                $member,
                $this->generateUrl(
                    'member_show',
                    ['lidnr' => $member->getLidnr()],
                ),
            ),
            $members,
        );
    }
}
