<?php

declare(strict_types=1);

namespace App\Controller\Database;

use App\Entity\Database\Decision;
use App\Entity\Database\Enums\MeetingTypes;
use App\Exception\Database\AnnulmentNotPossible;
use App\Exception\Database\DecisionStillReferenced;
use App\Form\Database\AbolishType;
use App\Form\Database\AnnulmentType;
use App\Form\Database\Board\DischargeType as BoardDischargeType;
use App\Form\Database\Board\InstallType as BoardInstallType;
use App\Form\Database\Board\ReleaseType as BoardReleaseType;
use App\Form\Database\BudgetType;
use App\Form\Database\DeleteDecisionType;
use App\Form\Database\FoundationType;
use App\Form\Database\InstallType;
use App\Form\Database\Key\GrantType as KeyGrantType;
use App\Form\Database\Key\WithdrawType as KeyWithdrawType;
use App\Form\Database\MemberFunctionType;
use App\Form\Database\MinutesType;
use App\Form\Database\OrganRegulationType;
use App\Form\Database\OtherType;
use App\Form\Report\ExportType;
use App\Form\SubmitButtons;
use App\Service\Database\Meeting as MeetingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use function array_key_exists;
use function assert;

// Deliberately without a class-level prefix: the decision list has always been served from `/export`, which cannot
// sit under the prefix the other actions share.
final class DecisionController extends AbstractController
{
    /**
     * Every kind of decision that can be recorded, keyed by the name it is entered and posted under.
     *
     * The key is also the partial that shows the form, and the order is the order of the tabs.
     */
    private const array FORM_TYPES = [
        'budget' => BudgetType::class,
        'organ_regulation' => OrganRegulationType::class,
        'foundation' => FoundationType::class,
        'abolish' => AbolishType::class,
        'install' => InstallType::class,
        'board_install' => BoardInstallType::class,
        'board_release' => BoardReleaseType::class,
        'board_discharge' => BoardDischargeType::class,
        'key_grant' => KeyGrantType::class,
        'key_withdraw' => KeyWithdrawType::class,
        'annulment' => AnnulmentType::class,
        'minutes' => MinutesType::class,
        'other' => OtherType::class,
    ];

    public function __construct(private readonly MeetingService $meetingService)
    {
    }

    /**
     * The decisions a decision can refer to, such as the one an annulment takes back.
     */
    #[Route(
        path: '/meeting/decision/search',
        name: 'decision_decision_search',
        methods: ['GET'],
    )]
    public function search(Request $request): JsonResponse
    {
        $meetingType = $request->query->get('meeting_type');
        $meetingNumber = $request->query->get('meeting_number');

        return $this->json($this->meetingService->searchDecisions(
            (string) $request->query->get(
                'q',
                '',
            ),
            null === $meetingType ? null : MeetingTypes::tryFrom((string) $meetingType),
            null === $meetingNumber ? null : (int) $meetingNumber,
            $request->query->getInt('point'),
            $request->query->getInt('decision'),
        ));
    }

    /**
     * Every form a decision can be entered with, for one place in a meeting.
     */
    #[Route(
        path: '/meeting/decision/{type}/{number}/{point}/{decision}',
        name: 'decision_decision_create',
        requirements: [
            'type' => 'ALV|BV|VV|Virt',
            'number' => '-?\d+',
            'point' => '\d+',
            'decision' => '\d+',
        ],
        methods: ['GET'],
    )]
    public function create(
        MeetingTypes $type,
        int $number,
        int $point,
        int $decision,
    ): Response {
        $meeting = $this->meetingService->getMeeting(
            $type,
            $number,
        );

        if (null === $meeting) {
            throw $this->createNotFoundException();
        }

        if (
            $this->meetingService->decisionExists(
                $type,
                $number,
                $point,
                $decision,
            )
        ) {
            return $this->render(
                'decision/decision/create.html.twig',
                [
                    'meeting' => $meeting,
                    'point' => $point,
                    'decision' => $decision,
                    'error' => true,
                ],
            );
        }

        $forms = [];

        foreach (self::FORM_TYPES as $name => $formType) {
            $forms[$name] = $this->createForm(
                $formType,
                new Decision(),
                [
                    'meeting' => $meeting,
                    'point' => $point,
                    'number' => $decision,
                ],
            )->createView();
        }

        $options = $this->meetingService->getDecisionOptions();

        return $this->render(
            'decision/decision/create.html.twig',
            [
                'meeting' => $meeting,
                'point' => $point,
                'decision' => $decision,
                'forms' => $forms,
                'installs' => $options->boardInstallations,
                'releasable_installs' => $options->releasableBoardInstallations,
                'grants' => $options->keyGrants,
                'member_function_form' => $this->memberFunctionForm(),
            ],
        );
    }

    /**
     * Record one decision, entered with one of the forms above.
     */
    #[Route(
        path: '/meeting/decision/{form}',
        name: 'decision_decision_form',
        requirements: ['form' => '[a-z][a-z_]*'],
        methods: ['POST'],
    )]
    public function form(
        Request $request,
        string $form,
    ): Response {
        if (
            !array_key_exists(
                $form,
                self::FORM_TYPES,
            )
        ) {
            throw $this->createNotFoundException();
        }

        $decisionForm = $this->createForm(
            self::FORM_TYPES[$form],
            new Decision(),
        );
        $decisionForm->handleRequest($request);

        if (
            $decisionForm->isSubmitted()
            && $decisionForm->isValid()
        ) {
            $decision = $decisionForm->getData();
            assert($decision instanceof Decision);

            try {
                $recorded = $this->meetingService->recordDecision($decision);

                return $this->render(
                    'decision/decision/created.html.twig',
                    [
                        'decision' => $recorded,
                        'contents' => $recorded->contents,
                        'warnings' => $recorded->warnings,
                    ],
                );
            } catch (AnnulmentNotPossible $e) {
                // The decision that is annulled is picked through the lookup on the `name` field, so that is where
                // the reason it cannot be annulled belongs.
                $decisionForm->get('name')->addError(new FormError($e->getMessage()));
            }
        }

        $options = $this->meetingService->getDecisionOptions();

        return $this->render(
            'decision/decision/form.html.twig',
            [
                'type' => $form,
                'form' => $decisionForm,
                // Relieving a board member is the only decision that leaves out those who have been relieved already.
                'installs' => 'board_release' === $form
                    ? $options->releasableBoardInstallations
                    : $options->boardInstallations,
                'grants' => $options->keyGrants,
                'member_function_form' => $this->memberFunctionForm(),
            ],
        );
    }

    #[Route(
        path: '/meeting/decision/delete/{type}/{number}/{point}/{decision}',
        name: 'decision_decision_delete',
        requirements: [
            'type' => 'ALV|BV|VV|Virt',
            'number' => '-?\d+',
            'point' => '\d+',
            'decision' => '\d+',
        ],
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function delete(
        Request $request,
        MeetingTypes $type,
        int $number,
        int $point,
        int $decision,
    ): Response {
        $form = $this->createForm(DeleteDecisionType::class);
        $form->handleRequest($request);

        $parameters = [
            'type' => $type,
            'number' => $number,
            'point' => $point,
            'decision' => $decision,
        ];

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {
            // Both answers submit the form, so the decision is only deleted when the confirming button was clicked.
            if (
                SubmitButtons::clicked(
                    $form,
                    'submit_yes',
                )
            ) {
                try {
                    $deleted = $this->meetingService->deleteDecision(
                        $type,
                        $number,
                        $point,
                        $decision,
                    );
                } catch (DecisionStillReferenced) {
                    return $this->render(
                        'decision/decision/delete.html.twig',
                        $parameters + ['error' => true],
                    );
                } catch (AnnulmentNotPossible) {
                    return $this->render(
                        'decision/decision/delete.html.twig',
                        $parameters + [
                            'error' => true,
                            'annulment' => true,
                        ],
                    );
                }

                // Two secretaries can hold this confirmation open at once, and the second one to answer it deletes
                // nothing. Reporting success either way would have them believe they removed something they did not.
                if ($deleted) {
                    $this->addFlash(
                        'success',
                        'The decision has been deleted.',
                    );
                } else {
                    $this->addFlash(
                        'warning',
                        'This decision no longer exists.',
                    );
                }
            }

            return $this->redirectToRoute(
                'decision_meeting_view',
                [
                    'type' => $type->value,
                    'number' => $number,
                ],
            );
        }

        return $this->render(
            'decision/decision/delete.html.twig',
            $parameters + ['form' => $form],
        );
    }

    /**
     * The decisions of a set of meetings, as the list that is published.
     */
    #[Route(
        path: '/export',
        name: 'decision_export_index',
        methods: [
            'GET',
            'POST',
        ],
    )]
    public function export(Request $request): Response
    {
        $form = $this->createForm(ExportType::class);
        $form->handleRequest($request);

        $latex = null;

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {
            /** @var array{meetings: string[]} $data */
            $data = $form->getData();

            // The decision list is a LaTeX document, which is shown as text to be pasted into a LaTeX editor rather
            // than as the page itself.
            $latex = $this->renderView(
                'decision/export/decisions.tex.twig',
                [
                    'categories' => $this->meetingService->exportDecisions($data['meetings']),
                ],
            );
        }

        return $this->render(
            'decision/export/index.html.twig',
            [
                'form' => $form,
                'latex' => $latex,
            ],
        );
    }

    /**
     * The function a member is given while an organ's membership is being edited.
     *
     * Being a member of the organ, or being an inactive one, is not offered: the page adds those installations itself
     * alongside whatever function is picked here.
     */
    private function memberFunctionForm(): FormInterface
    {
        return $this->createForm(
            MemberFunctionType::class,
            null,
            ['include_administrative' => false],
        );
    }
}
