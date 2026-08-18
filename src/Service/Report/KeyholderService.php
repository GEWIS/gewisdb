<?php

declare(strict_types=1);

namespace App\Service\Report;

use App\Entity\Report\Keyholder;
use App\Entity\Report\SubDecision\Key\Granting as ReportKeyGranting;
use App\Entity\Report\SubDecision\Key\Withdrawal as ReportKeyWithdrawal;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class KeyholderService
{
    public function __construct(
        #[Autowire(service: 'doctrine.orm.report_entity_manager')]
        private readonly EntityManagerInterface $emReport,
    ) {
    }

    public function generateGranting(ReportKeyGranting $granting): Keyholder
    {
        $rp = new ReflectionProperty(ReportKeyGranting::class, 'keyholder');
        if ($rp->isInitialized($granting)) {
            $keyholder = $granting->getKeyholder();
        } else {
            $keyholder = null;
        }

        if (null === $keyholder) {
            $keyholder = new Keyholder();
            $keyholder->setGrantingDec($granting);
            $granting->setKeyholder($keyholder);
        }

        $keyholder->setMember($granting->getMember());
        $keyholder->setExpirationDate($granting->getUntil());

        $this->emReport->persist($keyholder);

        return $keyholder;
    }

    public function generateWithdrawal(ReportKeyWithdrawal $withdrawal): void
    {
        $rp = new ReflectionProperty(ReportKeyGranting::class, 'keyholder');
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
