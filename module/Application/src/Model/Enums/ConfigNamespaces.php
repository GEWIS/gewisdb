<?php

declare(strict_types=1);

namespace Application\Model\Enums;

/**
 * The different namespaces in which configuration items can be created.
 * As a rule of thumb, a namespace should be restricted to one service or a well-defined set of a few services.
 *
 * Ideally these namespaces are defined inside the respective modules, but defining them as an enum allows for
 * verification in IDEs.
 */
enum ConfigNamespaces: string
{
    /* Database module */
    case DatabaseApi = 'database_api';
    case DatabaseMailman = 'database_mailman';
}
