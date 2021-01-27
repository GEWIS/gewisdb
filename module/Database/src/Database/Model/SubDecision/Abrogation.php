<?php

namespace Database\Model\SubDecision;

use Doctrine\ORM\Mapping as ORM;

use Database\Model\SubDecision;

/**
 * Abrogation of an organ.
 *
 * @ORM\Entity
 */
class Abrogation extends FoundationReference
{

    /**
     * Get the content.
     *
     * @todo implement this
     *
     * @return string
     */
    public function getContent()
    {
        // <type> <abbr> wordt opgeheven.
        switch ($this->getFoundation()->getOrganType()) {
        case Foundation::ORGAN_TYPE_COMMITTEE:
            $text = 'Commissie ';
            break;
        case Foundation::ORGAN_TYPE_AV_COMMITTEE:
            $text = 'AV-commissie ';
            break;
        case Foundation::ORGAN_TYPE_FRATERNITY:
            $text = 'Dispuut ';
            break;
        case Foundation::ORGAN_TYPE_KKK:
            $text = 'KKK ';
            break;
        case Foundation::ORGAN_TYPE_AVW:
            $text = 'AV-werkgroep ';
            break;
        case Foundation::ORGAN_TYPE_RVA:
            $text = 'RvA ';
            break;
        }
        return $text . $this->getFoundation()->getAbbr() . ' wordt opgeheven.';
    }
}
