<?php

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
use Database\Form\Other as OtherForm;
use Database\Mapper\Meeting as MeetingMapper;
use Database\Mapper\Organ as OrganMapper;
use Database\Service\Meeting as MeetingService;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class MeetingFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param $requestedName
     * @param array|null $options
     *
     * @return MeetingService
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
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
        /** @var OtherForm $otherForm */
        $otherForm = $container->get(OtherForm::class);
        /** @var MeetingMapper $meetingMapper */
        $meetingMapper = $container->get(MeetingMapper::class);
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
            $otherForm,
            $meetingMapper,
            $organMapper
        );
    }
}