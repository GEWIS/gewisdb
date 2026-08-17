<?php

declare(strict_types=1);

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Request every GET route and report the ones that fail.
 *
 * Parameters are filled from whatever is in the database, which is enough to catch the failures that only appear once
 * a template renders — a route that cannot generate the URL a template asks for, a property read before it is set, a
 * missing template. A route whose generated parameters do not match a real record answers 404 and is reported as
 * such; only a 5xx or an uncaught exception means something is actually wrong.
 *
 * Usage: docker compose exec -T web php scripts/smoke-routes.php
 */

require dirname(__DIR__) . '/vendor/autoload.php';

chdir(dirname(__DIR__));
(new Dotenv())->bootEnv('.env');

$kernel = new Kernel('dev', true);
$kernel->boot();
$c = $kernel->getContainer();
$router = $c->get('router');
$em = $c->get('doctrine')->getManager();
$conn = $em->getConnection();

$report = $c->get('doctrine')->getManager('report')->getConnection();

// Some of these live in ReportDB rather than the ledger; try both and shrug if neither has the table.
$one = static function (string $sql) use ($conn, $report): array {
    foreach ([$conn, $report] as $connection) {
        try {
            return $connection->fetchAssociative($sql) ?: [];
        } catch (Throwable) {
            continue;
        }
    }

    return [];
};

$meeting = $one('SELECT type, number FROM meeting ORDER BY number LIMIT 1');
$sub = $one('SELECT meeting_type, meeting_number, decision_point, decision_number, sequence FROM subdecision LIMIT 1');
$member = $one('SELECT lidnr FROM member LIMIT 1');
$organ = $one('SELECT id FROM organ LIMIT 1');
$prospective = $one('SELECT lidnr FROM prospectivemember LIMIT 1');
$savedQuery = $one('SELECT id FROM savedquery LIMIT 1');
$mailingList = $one('SELECT name FROM mailinglist LIMIT 1');
$user = $em->getRepository(App\Entity\User\User::class)->findOneBy([]);
$apiPrincipal = $one('SELECT id FROM apiprincipal LIMIT 1');

$values = [
    'type' => $meeting['type'] ?? 'ALV',
    'number' => $meeting['number'] ?? 1,
    'point' => $sub['decision_point'] ?? 1,
    'decision' => $sub['decision_number'] ?? 1,
    'sequence' => $sub['sequence'] ?? 1,
    'lidnr' => $member['lidnr'] ?? 1,
    'name' => $mailingList['name'] ?? 'news',
    'lang' => 'nl',
    'query' => $savedQuery['id'] ?? 1,
    'token' => 'deadbeef',
];

$idFor = static function (string $name) use ($organ, $prospective, $savedQuery, $user, $apiPrincipal, $member): int|string {
    return match (true) {
        str_contains($name, 'organ') => $organ['id'] ?? 1,
        str_contains($name, 'prospective') => $prospective['lidnr'] ?? 1,
        str_contains($name, 'query') => $savedQuery['id'] ?? 1,
        str_contains($name, 'api_principal') => $apiPrincipal['id'] ?? 1,
        str_contains($name, 'user') => $user?->getId() ?? 1,
        default => $member['lidnr'] ?? 1,
    };
};

$problems = [];

foreach ($router->getRouteCollection() as $name => $route) {
    if (str_starts_with($name, '_') || str_starts_with($name, 'api_') || str_starts_with($name, 'ux_')) {
        continue;
    }

    $methods = $route->getMethods();
    if ([] !== $methods && !in_array('GET', $methods, true)) {
        continue;
    }

    $params = [];
    foreach ($route->compile()->getPathVariables() as $var) {
        if (array_key_exists($var, $route->getDefaults())) {
            continue;
        }
        $params[$var] = match (true) {
            'id' === $var => $idFor($name),
            // `type` means an address type on the member routes and a meeting type everywhere else.
            'type' === $var && str_contains($name, 'address') => 'home',
            default => $values[$var] ?? 1,
        };
    }

    try {
        $url = $router->generate($name, $params);
    } catch (Throwable $e) {
        $problems[] = [$name, 'GENERATE', substr($e->getMessage(), 0, 130)];
        continue;
    }

    $request = Request::create($url);
    $session = new Session(new MockArraySessionStorage());
    $request->setSession($session);
    $request->cookies->set($session->getName(), $session->getId());

    if (null !== $user) {
        $session->set('_security_main', serialize(new UsernamePasswordToken($user, 'main', $user->getRoles())));
    }

    try {
        $response = $kernel->handle($request, HttpKernelInterface::MAIN_REQUEST, false);

        if ($response->getStatusCode() >= 500) {
            $problems[] = [$name, (string) $response->getStatusCode(), $url];
        }
    } catch (NotFoundHttpException) {
        // The generated parameters name no real record. That is this script's guess being wrong, not the route.
        continue;
    } catch (Throwable $e) {
        $problems[] = [$name, 'THROW', $url . "\n         " . substr($e->getMessage(), 0, 200)];
    }
}

foreach ($problems as [$n, $c, $d]) {
    printf("%-9s %-38s %s\n", $c, $n, $d);
}
printf("\n%d problem(s)\n", count($problems));
