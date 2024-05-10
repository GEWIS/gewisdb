<?php

declare(strict_types=1);

namespace Application\Model\Enums;

/**
 * Enum for the different organ types.
 */
enum OrganTypes: string
{
    case Committee = 'committee';
    case AVC = 'avc';
    case Fraternity = 'fraternity';
    case KCC = 'kcc';
    case AVW = 'avw';
    case RvA = 'rva';

    public function getName(): string
    {
        return match ($this) {
            self::Committee => 'Commissie',
            self::AVC => 'ALV-Commissie',
            self::Fraternity => 'Dispuut',
            self::KCC => 'KCC',
            self::AVW => 'ALV-Werkgroep',
            self::RvA => 'RvA',
        };
    }

    public function getAlternativeName(): string
    {
        return match ($this) {
            self::Committee => 'Committee',
            self::AVC => 'GMM Committee',
            self::Fraternity => 'Fraternity',
            self::KCC => 'Financial Audit Committee',
            self::AVW => 'GMM Taskforce',
            self::RvA => 'Advisory Board',
        };
    }
}
