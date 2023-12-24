<?php

declare(strict_types=1);

namespace Report\Service;

use Doctrine\ORM\EntityManager;
use Laminas\ProgressBar\Adapter\Console;
use Laminas\ProgressBar\ProgressBar;
use LogicException;
use ReflectionProperty;
use Report\Model\Keyholder as KeyholderModel;
use Report\Model\SubDecision\Key\Granting as ReportKeyGrantingModel;
use Report\Model\SubDecision\Key\Withdrawal as ReportKeyWithdrawalModel;

use function count;

class Keyholder
{
    public function __construct(private readonly EntityManager $emReport)
    {
    }

    /**
     * Export keyholder info.
     */
    public function generate(): void
    {
        $grantingRepo = $this->emReport->getRepository(ReportKeyGrantingModel::class);

        /** @var array<array-key, ReportKeyGrantingModel> $grantings */
        $grantings = $grantingRepo->findBy([], [
            'meeting_type' => 'DESC',
            'meeting_number' => 'ASC',
            'decision_point' => 'ASC',
            'decision_number' => 'ASC',
            'number' => 'ASC',
        ]);

        $adapter = new Console();
        $progress = new ProgressBar($adapter, 0, count($grantings));
        $num = 0;

        foreach ($grantings as $granting) {
            $keyholder = $this->generateGranting($granting);

            if (null !== $granting->getWithdrawal()) {
                $this->generateWithdrawal($granting->getWithdrawal());
            }

            $this->emReport->persist($keyholder);
            $progress->update(++$num);
        }

        $this->emReport->flush();
        $progress->finish();
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
        }

        $keyholder->setMember($granting->getMember());
        $keyholder->setExpirationDate($granting->getUntil());

        $this->emReport->persist($keyholder);
        $this->emReport->flush();

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
        $this->emReport->flush();
    }
}
