<?php

declare(strict_types=1);

namespace App\Service\Report;

use App\Entity\Report\Keyholder;
use App\Entity\Report\SubDecision\Key\Granting as ReportKeyGranting;
use App\Entity\Report\SubDecision\Key\Withdrawal as ReportKeyWithdrawal;
use Doctrine\ORM\EntityManagerInterface;
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
        $keyholder = $this->findKeyholder($granting);

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
        $keyholder = $this->findKeyholder($withdrawal->getGranting());

        if (null === $keyholder) {
            // The granting this withdrawal takes back never took effect, so there is no key to withdraw. That is
            // what the ledger says whenever the granting was annulled before this point.
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
    private function findKeyholder(ReportKeyGranting $granting): ?Keyholder
    {
        $rp = new ReflectionProperty(
            ReportKeyGranting::class,
            'keyholder',
        );

        if ($rp->isInitialized($granting)) {
            return $granting->getKeyholder();
        }

        return $this->emReport->getRepository(Keyholder::class)
            ->findOneBy(['grantingDec' => $granting]);
    }
}
