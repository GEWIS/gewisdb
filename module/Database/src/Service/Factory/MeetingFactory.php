<?php

declare(strict_types=1);

namespace Database\Service\Factory;

use Database\Form\Abolish as AbolishForm;
use Database\Form\Board\Discharge as BoardDischargeForm;
use Database\Form\Board\Install as BoardInstallForm;
use Database\Form\Board\Release as BoardReleaseForm;
use Database\Form\Budget as BudgetForm;
use Database\Form\CreateMeeting as CreateMeetingForm;
use Database\Form\DeleteDecision as DeleteDecisionForm;
use Database\Form\Destroy as DestroyForm;
use Database\Form\Export as ExportForm;
use Database\Form\Foundation as FoundationForm;
use Database\Form\Install as InstallForm;
use Database\Form\Key\Grant as KeyGrantForm;
use Database\Form\Key\Withdraw as KeyWithdrawForm;
use Database\Form\Other as OtherForm;
use Database\Mapper\Meeting as MeetingMapper;
use Database\Mapper\Member as MemberMapper;
use Database\Mapper\Organ as OrganMapper;
use Database\Service\Meeting as MeetingService;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class MeetingFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null,
    ): MeetingService {
        /** @var AbolishForm $abolishForm */
        $abolishForm = $container->get(AbolishForm::class);
        /** @var BoardDischargeForm $boardDischargeForm */
        $boardDischargeForm = $container->get(BoardDischargeForm::class);
        /** @var BoardInstallForm $boardInstallForm */
        $boardInstallForm = $container->get(BoardInstallForm::class);
        /** @var BoardReleaseForm $boardReleaseForm */
        $boardReleaseForm = $container->get(BoardReleaseForm::class);
        /** @var BudgetForm $budgetForm */
        $budgetForm = $container->get(BudgetForm::class);
        /** @var CreateMeetingForm $createMeetingForm */
        $createMeetingForm = $container->get(CreateMeetingForm::class);
        /** @var DeleteDecisionForm $deleteDecisionForm */
        $deleteDecisionForm = $container->get(DeleteDecisionForm::class);
        /** @var DestroyForm $destroyForm */
        $destroyForm = $container->get(DestroyForm::class);
        /** @var ExportForm $exportForm */
        $exportForm = $container->get(ExportForm::class);
        /** @var FoundationForm $foundationForm */
        $foundationForm = $container->get(FoundationForm::class);
        /** @var InstallForm $installForm */
        $installForm = $container->get(InstallForm::class);
        /** @var KeyGrantForm $keyGrantForm */
        $keyGrantForm = $container->get(KeyGrantForm::class);
        /** @var KeyWithdrawForm $keyWithdrawForm */
        $keyWithdrawForm = $container->get(KeyWithdrawForm::class);
        /** @var OtherForm $otherForm */
        $otherForm = $container->get(OtherForm::class);
        /** @var MeetingMapper $meetingMapper */
        $meetingMapper = $container->get(MeetingMapper::class);
        /** @var MemberMapper $memberMapper */
        $memberMapper = $container->get(MemberMapper::class);
        /** @var OrganMapper $organMapper */
        $organMapper = $container->get(OrganMapper::class);

        return new MeetingService(
            $abolishForm,
            $boardDischargeForm,
            $boardInstallForm,
            $boardReleaseForm,
            $budgetForm,
            $createMeetingForm,
            $deleteDecisionForm,
            $destroyForm,
            $exportForm,
            $foundationForm,
            $installForm,
            $keyGrantForm,
            $keyWithdrawForm,
            $otherForm,
            $meetingMapper,
            $memberMapper,
            $organMapper,
        );
    }
}
