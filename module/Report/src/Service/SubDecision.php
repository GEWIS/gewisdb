<?php

declare(strict_types=1);

namespace Report\Service;

use Doctrine\ORM\EntityManager;
use Report\Model\SubDecision as SubDecisionModel;
use Report\Model\SubDecision\Foundation as FoundationModel;
use Report\Model\SubDecision\Abrogation as AbrogationModel;
use Report\Model\SubDecision\Installation as InstallationModel;
use Report\Model\SubDecision\Discharge as DischargeModel;
use Report\Model\SubDecision\Key\Granting as KeyGrantingModel;
use Report\Model\SubDecision\Key\Withdrawal as KeyWithdrawalModel;
use Report\Model\SubDecision\Board\Installation as BoardInstallationModel;
use Report\Model\SubDecision\Board\Release as BoardReleaseModel;
use Report\Model\SubDecision\Board\Discharge as BoardDischargeModel;
use Report\Service\Board as BoardService;
use Report\Service\Keyholder as KeyholderService;
use Report\Service\Organ as OrganService;

class SubDecision
{
    public function __construct(
        private readonly EntityManager $emReport,
        private readonly BoardService $boardService,
        private readonly KeyholderService $keyholderService,
        private readonly OrganService $organService,
    ) {
    }

    /**
     * Generates related entities of a subdecision into ReportDB.
     */
    public function generateRelated(SubDecisionModel $subDecision): void
    {
        switch (true) {
            // Board-related
            case $subDecision instanceof BoardInstallationModel:
                $this->boardService->generateInstallation($subDecision);
                break;

            case $subDecision instanceof BoardReleaseModel:
                $this->boardService->generateRelease($subDecision);
                break;

            case $subDecision instanceof BoardDischargeModel:
                $this->boardService->generateDischarge($subDecision);
                break;

            // Keyholder-related
            case $subDecision instanceof KeyGrantingModel:
                $this->keyholderService->generateGranting($subDecision);
                break;

            case $subDecision instanceof KeyWithdrawalModel:
                $this->keyholderService->generateWithdrawal($subDecision);
                break;

            // Organ-related
            case $subDecision instanceof FoundationModel:
                $this->organService->generateFoundation($subDecision);
                break;

            case $subDecision instanceof AbrogationModel:
                $this->organService->generateAbrogation($subDecision);
                break;

            case $subDecision instanceof InstallationModel:
                $this->organService->generateInstallation($subDecision);
                break;

            case $subDecision instanceof DischargeModel:
                $this->organService->generateDischarge($subDecision);
                break;
        }

        $this->emReport->persist($subDecision);
    }
}
