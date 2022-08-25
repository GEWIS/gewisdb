<?php

namespace Database\Validator;

use Laminas\Validator\Iban;

/**
 * Extension of the IBAN validator that forces that a country pays in euros
 */
class EuroIban extends Iban
{
    protected $allowNonSepa = false;

    /**
     * The SEPA country codes that have the EURO
     *
     * @var string[] ISO 3166-1 codes
     */
    protected static $sepaCountries = [
        'AT',
        'AD',
        'BE',
        'CY',
        'EE',
        'FI',
        'FR',
        'DE',
        'GR',
        'IE',
        'IT',
        'LV',
        'LT',
        'LU',
        'MT',
        'MC',
        'NL',
        'PT',
        'SK',
        'SI',
        'ES',
        'SM',
    ];

    protected $messageTemplates = [
        self::NOTSUPPORTED     => 'Unknown country within the IBAN',
        self::SEPANOTSUPPORTED => 'Only countries that participate in SEPA and pay with euros are supported',
        self::FALSEFORMAT      => 'The input has a false IBAN format',
        self::CHECKFAILED      => 'The input has failed the IBAN check',
    ];
}
