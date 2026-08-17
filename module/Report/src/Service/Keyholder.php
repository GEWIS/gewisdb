<?php

declare(strict_types=1);

namespace Report\Service;

use Doctrine\ORM\EntityManager;
use LogicException;
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
        $rp = new ReflectionProperty(ReportKeyGrantingModel::class, 'keyholder');
        if ($rp->isInitialized($granting)) {
            $keyholder = $granting->getKeyholder();
        } else {
            $keyholder = null;
        }

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
        $rp = new ReflectionProperty(ReportKeyGrantingModel::class, 'keyholder');
        if ($rp->isInitialized($withdrawal->getGranting())) {
            $keyholder = $withdrawal->getGranting()->getKeyholder();
        } else {
            $keyholder = null;
        }

        if (null === $keyholder) {
            throw new LogicException('Key withdrawal without Keyholder');
        }

        $keyholder->setWithdrawnDate($withdrawal->getWithdrawnOn());

        $this->emReport->persist($keyholder);
    }
}
