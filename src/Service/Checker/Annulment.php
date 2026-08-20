<?php

declare(strict_types=1);

namespace App\Service\Checker;

use App\Entity\Database\Meeting as MeetingModel;
use App\Entity\Database\SubDecision\Annulment as AnnulmentModel;
use App\Repository\Checker\AnnulmentRepository;
use App\Service\Database\Annulment as DecisionAnnulmentService;

use function array_filter;
use function array_values;

class Annulment
{
    public function __construct(
        private readonly AnnulmentRepository $annulmentRepository,
        private readonly DecisionAnnulmentService $annulmentService,
    ) {
    }

    /**
     * Get the annulments that were made during `$meeting`.
     *
     * @return AnnulmentModel[]
     */
    public function getAnnulmentsAtMeeting(MeetingModel $meeting): array
    {
        return $this->annulmentRepository->getAnnulmentsAtMeeting($meeting);
    }

    /**
     * Whether the given annulment annuls a decision that itself annuls another one.
     */
    public function annulsAnAnnulment(AnnulmentModel $annulment): bool
    {
        return $this->annulmentService->isAnnulling($annulment->getTarget());
    }

    /**
     * Whether the given annulment annuls a decision that was only taken after it.
     */
    public function annulsALaterDecision(AnnulmentModel $annulment): bool
    {
        return !$this->annulmentService->isBefore(
            $annulment->getTarget(),
            $annulment->getDecision(),
        );
    }

    /**
     * Get the annulments that already annulled the same decision before the given one did.
     *
     * Only the earlier ones, so that the annulment which did the work is not reported alongside the ones that had
     * nothing left to take back.
     *
     * @return AnnulmentModel[]
     */
    public function getEarlierAnnulments(AnnulmentModel $annulment): array
    {
        return array_values(array_filter(
            $this->annulmentRepository->getAnnulmentsForDecision($annulment->getTarget()),
            fn (AnnulmentModel $other): bool => $other !== $annulment
                && $this->annulmentService->isBefore(
                    $other->getDecision(),
                    $annulment->getDecision(),
                ),
        ));
    }
}
