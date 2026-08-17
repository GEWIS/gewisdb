<?php

declare(strict_types=1);

/**
 * Dump the DDL that the ported entities under src/Entity/<Domain> imply, using whichever ORM/DBAL is installed.
 *
 * Lets the ported mapping be compared against the recorded schema without Symfony or doctrine-bundle being
 * installable yet. The naming strategy and type registration mirror config/packages/doctrine.yaml; if they drift
 * apart this comparison stops meaning anything.
 *
 * Usage: php scripts/goldens/entity-schema.php <Domain> [<Domain> ...]
 */

$root = dirname(__DIR__, 2);

require $root . '/vendor/autoload.php';

use App\Doctrine\Types\StringableDateTimeType;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\DefaultNamingStrategy;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Tools\SchemaTool;

$domains = array_slice($argv, 1);
if ([] === $domains) {
    fwrite(STDERR, "usage: entity-schema.php <Domain> [<Domain> ...]\n");
    exit(2);
}

$paths = array_map(static fn (string $d): string => $root . '/src/Entity/' . $d, $domains);

foreach ($paths as $path) {
    if (!is_dir($path)) {
        fwrite(STDERR, "no such domain directory: {$path}\n");
        exit(2);
    }
}

$config = new Configuration();
$config->setMetadataDriverImpl(new AttributeDriver($paths));
$config->setNamingStrategy(new DefaultNamingStrategy());
$config->setProxyDir(sys_get_temp_dir() . '/gewisdb-entity-schema');
$config->setProxyNamespace('GewisdbEntitySchemaProxies');
$config->setAutoGenerateProxyClasses(false);

if (!Type::hasType('stringable_datetime')) {
    Type::addType('stringable_datetime', StringableDateTimeType::class);
}

$connection = DriverManager::getConnection([
    'driver' => 'pdo_pgsql',
    'host' => getenv('DOCTRINE_DEFAULT_HOST') ?: 'postgresql',
    'port' => (int) (getenv('DOCTRINE_DEFAULT_PORT') ?: 5432),
    'user' => getenv('DOCTRINE_DEFAULT_USER') ?: 'gewisdb',
    'password' => getenv('DOCTRINE_DEFAULT_PASSWORD') ?: 'gewisdb',
    'dbname' => getenv('DOCTRINE_DEFAULT_DATABASE') ?: 'gewisdb',
], $config);

$em = new EntityManager($connection, $config);
$metadata = $em->getMetadataFactory()->getAllMetadata();

if ([] === $metadata) {
    fwrite(STDERR, 'no metadata found under: ' . implode(', ', $paths) . "\n");
    exit(1);
}

fwrite(STDERR, sprintf("entities: %d\n", count($metadata)));

$sql = (new SchemaTool($em))->getCreateSchemaSql($metadata);
sort($sql, SORT_STRING);

foreach ($sql as $line) {
    echo $line, ";\n";
}
