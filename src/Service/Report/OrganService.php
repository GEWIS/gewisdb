<?php

declare(strict_types=1);

namespace App\Service\Report;

use App\Entity\Report\Organ as ReportOrgan;
use App\Entity\Report\OrganMember as ReportOrganMember;
use App\Entity\Report\SubDecision\Abrogation as ReportAbrogation;
use App\Entity\Report\SubDecision\Discharge as ReportDischarge;
use App\Entity\Report\SubDecision\Foundation as ReportFoundation;
use App\Entity\Report\SubDecision\Installation as ReportInstallation;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class OrganService
{
    public function __construct(
        #[Autowire(service: 'doctrine.orm.report_entity_manager')]
        private readonly EntityManagerInterface $emReport,
    ) {
    }

    public function generateFoundation(ReportFoundation $foundation): ReportOrgan
    {
        // see if there already is an organ (with a slight hack)
        $rp = new ReflectionProperty(
            ReportFoundation::class,
            'organ',
        );
        if ($rp->isInitialized($foundation)) {
            $repOrgan = $foundation->getOrgan();
        } else {
            $repOrgan = null;
        }

        if (null === $repOrgan) {
            $repOrgan = new ReportOrgan();
            $repOrgan->setFoundation($foundation);
            $foundation->setOrgan($repOrgan);
        }

        $repOrgan->setAbbr($foundation->getAbbr());
        $repOrgan->setName($foundation->getName());
        $repOrgan->setType($foundation->getOrganType());
        $repOrgan->setFoundationDate($foundation->getDecision()->getMeeting()->getDate());

        // To ensure that the subdecision is correctly linked to the organ.
        $repOrgan->addSubdecision($foundation);

        $this->emReport->persist($repOrgan);

        return $repOrgan;
    }

    public function generateAbrogation(ReportAbrogation $ref): void
    {
        $rp = new ReflectionProperty(
            ReportFoundation::class,
            'organ',
        );
        if ($rp->isInitialized($ref->getFoundation())) {
            $repOrgan = $ref->getFoundation()->getOrgan();
        } else {
            $repOrgan = null;
        }

        if (null === $repOrgan) {
            // Grabbing the organ from the foundation doesn't work when it has not been saved yet
            $repo = $this->emReport->getRepository(ReportOrgan::class);
            $repOrgan = $repo->findOneBy([
                'foundation' => $ref->getFoundation(),
            ]);

            if (null === $repOrgan) {
                throw new LogicException('Abrogation without Organ');
            }
        }

        $abrogationDate = $ref->getDecision()->getMeeting()->getDate();
        $repOrgan->setAbrogationDate($abrogationDate);

        // Abolishing an organ discharges whoever is still in it; there is no separate decision for that.
        foreach ($repOrgan->getMembers() as $organMember) {
            if (null !== $organMember->getDischargeDate()) {
                continue;
            }

            $organMember->setDischargeDate($abrogationDate);
            $this->emReport->persist($organMember);
        }

        // To ensure that the subdecision is correctly linked to the organ.
        $repOrgan->addSubdecision($ref);

        $this->emReport->persist($repOrgan);
    }

    public function generateInstallation(ReportInstallation $ref): void
    {
        $repo = $this->emReport->getRepository(ReportOrgan::class);
        // get full reference
        $rp = new ReflectionProperty(
            ReportInstallation::class,
            'organMember',
        );
        if ($rp->isInitialized($ref)) {
            $organMember = $ref->getOrganMember();
        } else {
            $organMember = null;
        }

        $rp = new ReflectionProperty(
            ReportFoundation::class,
            'organ',
        );
        if ($rp->isInitialized($ref->getFoundation())) {
            $repOrgan = $ref->getFoundation()->getOrgan();
        } else {
            $repOrgan = null;
        }

        if (null === $repOrgan) {
            // Grabbing the organ from the foundation doesn't work when it has not been saved yet
            $repOrgan = $repo->findOneBy([
                'foundation' => $ref->getFoundation(),
            ]);

            if (null === $repOrgan) {
                throw new LogicException('Installation without Organ');
            }
        }

        if (null === $organMember) {
            $organMember = new ReportOrganMember();
            // set the ID stuff
            $organMember->setOrgan($repOrgan);
            $organMember->setMember($ref->getMember());
            $function = $ref->getFunction();

            $organMember->setFunction($function);
            $organMember->setInstallDate($ref->getDecision()->getMeeting()->getDate());
        }

        $organMember->setInstallation($ref);
        $ref->setOrganMember($organMember);
        $repOrgan->addMember($organMember);
        $discharge = $ref->getDischarge();

        if (null !== $discharge) {
            $organMember->setDischargeDate($discharge->getDecision()->getMeeting()->getDate());

            // also add discharge as related
            $repOrgan->addSubdecision($discharge);
        }

        if (
            null !== $repOrgan->getAbrogationDate()
            && null === $organMember->getDischargeDate()
        ) {
            $organMember->setDischargeDate($repOrgan->getAbrogationDate());
        }

        // To ensure that the subdecision is correctly linked to the organ.
        $repOrgan->addSubdecision($ref);

        $this->emReport->persist($organMember);
    }

    public function generateDischarge(ReportDischarge $ref): void
    {
        // The installation's organMember is the inverse side of the relation; it is only hydrated when the installation
        // is (re)loaded in a fresh session. Within a single session (e.g. seeding, where the install and discharge are
        // processed back-to-back) it is not, so look the OrganMember up by its installation instead.
        $rp = new ReflectionProperty(
            ReportInstallation::class,
            'organMember',
        );
        if ($rp->isInitialized($ref->getInstallation())) {
            $organMember = $ref->getInstallation()->getOrganMember();
        } else {
            $organMember = $this->emReport->getRepository(ReportOrganMember::class)
                ->findOneBy(['installation' => $ref->getInstallation()]);
        }

        if (null === $organMember) {
            // The installation this discharge undoes never took effect, so there is nobody in the body to discharge.
            // That is what the ledger says whenever the installation was annulled before this point.
            return;
        }

        $rp = new ReflectionProperty(
            ReportFoundation::class,
            'organ',
        );
        if ($rp->isInitialized($organMember->getInstallation()->getFoundation())) {
            $repOrgan = $organMember->getInstallation()->getFoundation()->getOrgan();
        } else {
            $repOrgan = null;
        }

        if (null === $repOrgan) {
            // Grabbing the organ from the foundation doesn't work when it has not been saved yet
            $repOrgan = $this->emReport->getRepository(ReportOrgan::class)->findOneBy([
                'foundation' => $organMember->getInstallation()->getFoundation(),
            ]);

            if (null === $repOrgan) {
                throw new LogicException('Discharge without Organ');
            }
        }

        $organMember->setDischargeDate($ref->getDecision()->getMeeting()->getDate());

        // To ensure that the subdecision is correctly linked to the organ.
        $repOrgan->addSubdecision($ref);

        $this->emReport->persist($organMember);
    }
}
