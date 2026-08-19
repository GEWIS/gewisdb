<?php

declare(strict_types=1);

namespace Report\Service;

use Doctrine\ORM\EntityManager;
use ReflectionProperty;
use Report\Model\Keyholder as KeyholderModel;
use Report\Model\SubDecision\Key\Granting as ReportKeyGrantingModel;
use Report\Model\SubDecision\Key\Withdrawal as ReportKeyWithdrawalModel;

class Keyholder
{
    public function __construct(private readonly EntityManager $emReport)
    {
    }

    public function generateGranting(ReportKeyGrantingModel $granting): KeyholderModel
    {
        $keyholder = $this->findKeyholder($granting);

        if (null === $keyholder) {
            $keyholder = new KeyholderModel();
            $keyholder->setGrantingDec($granting);
            $granting->setKeyholder($keyholder);
        }

        $keyholder->setMember($granting->getMember());
        $keyholder->setExpirationDate($granting->getUntil());

        $this->emReport->persist($keyholder);

        return $keyholder;
    }

    public function generateWithdrawal(ReportKeyWithdrawalModel $withdrawal): void
    {
        $keyholder = $this->findKeyholder($withdrawal->getGranting());

        if (null === $keyholder) {
            // The granting this withdrawal takes back never took effect, so there is no key to withdraw. That is what
            // the ledger says whenever the granting was annulled before this point.
            return;
        }

        $keyholder->setWithdrawnDate($withdrawal->getWithdrawnOn());

        $this->emReport->persist($keyholder);
    }

    /**
     * Find the keyholder a granting brought into being, if it has one.
     *
     * The granting's keyholder is the inverse side of the relation; it is only hydrated when the granting is
     * (re)loaded in a fresh session. Within a single session it is kept in step by hand, but a granting that was
     * never granted here has neither, so fall back to looking the keyholder up by its granting.
     */
    private function findKeyholder(ReportKeyGrantingModel $granting): ?KeyholderModel
    {
        $rp = new ReflectionProperty(ReportKeyGrantingModel::class, 'keyholder');

        if ($rp->isInitialized($granting)) {
            return $granting->getKeyholder();
        }

        return $this->emReport->getRepository(KeyholderModel::class)
            ->findOneBy(['grantingDec' => $granting]);
    }
}
