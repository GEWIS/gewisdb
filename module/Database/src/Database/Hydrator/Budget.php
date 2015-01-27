<?php

namespace Database\Hydrator;

use Database\Model\Decision;
use Database\Model\SubDecision\Budget as BudgetDecision;
use Database\Model\SubDecision\Reckoning as ReckoningDecision;
use Doctrine\Common\Proxy\Exception\InvalidArgumentException;

class Budget extends AbstractDecision
{

    private $meetingService;

    public function __construct(\Database\Service\Meeting $meetingService)
    {
        $this->meetingService = $meetingService;
    }

    /**
     * Budget hydration
     *
     * @param array $data
     * @param SubDecision $object
     *
     * @return SubDecision
     *
     * @throws \InvalidArgumentException when $object is not a SubDecision
     */
    public function hydrate(array $data, $object)
    {
        $object = parent::hydrate($data, $object);

        if ($data['type'] == 'budget') {
            $subdecision = new BudgetDecision();
        } else {
            $subdecision = new ReckoningDecision();
        }
        //\Zend\Debug\Debug::dump($data);
        $subdecision->setNumber(1);

        $date = new \DateTime($data['date']);
        $subdecision->setDate($date);

        $subdecision->setName($data['name']);
        $subdecision->setAuthor($data['author']);
        $subdecision->setVersion($data['version']);
        $subdecision->setApproval($data['approve']);
        $subdecision->setChanges($data['changes']);

        // In the budget a possible foundation reference is not
        // given as a references, but rather as a name. It is already
        // validated that if a name is given, the name exists
        if (isset($data['organ']) && $data['organ'] !== '') {
            $this->setOrgan($subdecision, $data['organ']);
        }
        
        $subdecision->setDecision($object);

        return $object;
    }

    /**
     * @param BudgetDecision $decision
     * @param $organName
     */
    private function setOrgan(BudgetDecision $decision, $organName) {
        $organs = $this->meetingService->organSearch($organName);

        // hack needed because organSearch searches for organs with
        // a name like the name given, or an abbreviation like a name given
        // so incorrect results may be returned too.
        foreach ($organs as $organ) {
            if ($organ->getName() == $organName) {
                $goodOrgan = $organ;
                break;
            }
        }

        // Just in case our validator has failed.
        if (!isset($goodOrgan)) {
            throw new InvalidArgumentException('Organ does not exists');
        }
        $decision->setFoundation($goodOrgan);
    }
}
