<?php

declare(strict_types=1);

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');

$kernel = new Kernel('dev', true);
$kernel->boot();

// phpstan-doctrine reads the mapping of the default manager; the report manager maps the same entities from its own
// copy under App\Entity\Report.
return $kernel->getContainer()->get('doctrine')->getManager();
