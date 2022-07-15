<?php

namespace Report\Service;

use Doctrine\ORM\EntityManager;
use Laminas\ProgressBar\Adapter\Console;
use Laminas\ProgressBar\ProgressBar;
use Report\Model\Organ as ReportOrgan;
use Report\Model\OrganMember;
use Report\Model\SubDecision\Abrogation;
use Report\Model\SubDecision\Installation;

class Organ
{
    /** @var EntityManager $emReport */
    private $emReport;

    /**
     * @param EntityManager $emReport
     */
    public function __construct(EntityManager $emReport)
    {
        $this->emReport = $emReport;
    }

    /**
     * Export organ info.
     */
    public function generate()
    {
        $foundationRepo = $this->emReport->getRepository('Report\Model\SubDecision\Foundation');

        $foundations = $foundationRepo->findBy([], [
            'meeting_type' => 'DESC',
            'meeting_number' => 'ASC',
            'decision_point' => 'ASC',
            'decision_number' => 'ASC',
            'number' => 'ASC',
        ]);

        $adapter = new Console();
        $progress = new ProgressBar($adapter, 0, count($foundations));

        $num = 0;
        foreach ($foundations as $foundation) {
            // see if there already is an organ
            $repOrgan = $this->generateFoundation($foundation);

            /**
             * Also find all related subdecisions.
             *
             * Types of subdecisions that can be related to an organ:
             * - foundation
             * - abrogation
             * - installation
             * - discharge
             */
            $repOrgan->addSubdecision($foundation);

            // get the abrogation date and find organ members
            foreach ($foundation->getReferences() as $ref) {
                // first add as related subdecision
                $repOrgan->addSubdecision($ref);

                if ($ref instanceof Abrogation) {
                    $this->generateAbrogation($ref);
                }

                if ($ref instanceof Installation) {
                    $this->generateInstallation($ref);
                }
            }

            $this->emReport->persist($repOrgan);
            $this->emReport->flush();
            $progress->update(++$num);
        }

        $this->emReport->flush();
        $progress->finish();
    }

    public function generateFoundation($foundation)
    {
        // see if there already is an organ
        $repOrgan = $foundation->getOrgan();

        if (null === $repOrgan) {
            $repOrgan = new ReportOrgan();
            $repOrgan->setFoundation($foundation);
        }

        $repOrgan->setAbbr($foundation->getAbbr());
        $repOrgan->setName($foundation->getName());
        $repOrgan->setType($foundation->getOrganType());
        $repOrgan->setFoundationDate($foundation->getDecision()->getMeeting()->getDate());
        $this->emReport->persist($repOrgan);
        $this->emReport->flush();

        return $repOrgan;
    }

    public function generateAbrogation($ref)
    {
        $repOrgan = $ref->getFoundation()->getOrgan();

        if ($repOrgan === null) {
            // Grabbing the organ from the foundation doesn't work when it has not been saved yet
            $repo = $this->emReport->getRepository('Report\Model\Organ');
            $repOrgan = $repo->findOneBy([
                'foundation' => $ref->getFoundation(),
            ]);

            if ($repOrgan === null) {
                throw new \LogicException('Abrogation without Organ');
            }
        }

        $repOrgan->setAbrogationDate($ref->getDecision()->getMeeting()->getDate());
    }

    public function generateInstallation($ref)
    {
        $repo = $this->emReport->getRepository('Report\Model\Organ');
        // get full reference
        $organMember = $ref->getOrganMember();
        $repOrgan = $ref->getFoundation()->getOrgan();

        if ($repOrgan === null) {
            // Grabbing the organ from the foundation doesn't work when it has not been saved yet
            $repOrgan = $repo->findOneBy([
                'foundation' => $ref->getFoundation(),
            ]);

            if ($repOrgan === null) {
                throw new \LogicException('Installation without Organ');
            }
        }

        if (null === $organMember) {
            $organMember = new OrganMember();
            // set the ID stuff
            $organMember->setOrgan($repOrgan);
            $organMember->setMember($ref->getMember());
            $function = $ref->getFunction();

            if (null === $function) {
                $function = 'Lid';
            }

            $organMember->setFunction($function);
            $organMember->setInstallDate($ref->getDecision()->getMeeting()->getDate());
        }

        $organMember->setInstallation($ref);
        $discharge = $ref->getDischarge();

        if (null !== $discharge) {
            $organMember->setDischargeDate($discharge->getDecision()->getMeeting()->getDate());

            // also add discharge as related
            $repOrgan->addSubdecision($discharge);
        }

        if ($repOrgan->getAbrogationDate() !== null && $organMember->getDischargeDate() === null) {
            $organMember->setDischargeDate($repOrgan->getAbrogationDate());
        }

        $this->emReport->persist($organMember);
    }

    public function generateDischarge($ref)
    {
        $organMember = $ref->getInstallation()->getOrganMember();

        if ($organMember === null) {
            throw new \LogicException('Discharge without OrganMember');
        }

        $organMember->setDischargeDate($ref->getDecision()->getMeeting()->getDate());
        $this->emReport->persist($organMember);
    }
}
