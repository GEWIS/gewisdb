<?php

namespace Application\Model\Enums;

/**
 * Enum for the different organ types.
 */
enum OrganTypes: string
{
    case Committee = 'committee';
    case AVC = 'avc';
    case Fraternity = 'fraternity';
    case KKK = 'kkk';
    case AVW = 'avw';
    case RvA = 'rva';

    public function getName(): string
    {
        return match ($this) {
            self::Committee => 'Commissie',
            self::AVC => 'AV-commissie',
            self::Fraternity => 'Dispuut',
            self::KKK => 'KKK',
            self::AVW => 'AV-werkgroep',
            self::RvA => 'RvA',
        };
    }
}
