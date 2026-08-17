<?php

declare(strict_types=1);

/**
 * Bundles registered with the Symfony kernel.
 *
 * Deliberately minimal while the migration is in progress: this scaffold runs *alongside* the live Laminas
 * application rather than replacing it, so only bundles that can coexist are registered here.
 *
 * Notably absent is DoctrineBundle. It requires Doctrine ORM 3, while `doctrine/doctrine-laminas-hydrator` and
 * `doctrine/doctrine-orm-module` — which the Laminas application still depends on — are ORM 2 only. The two cannot
 * be installed at once, so the Doctrine layer arrives with the ORM 3 upgrade in GH-558 / GH-559 rather than here.
 */

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
];
