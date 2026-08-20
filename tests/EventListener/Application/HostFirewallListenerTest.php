<?php

declare(strict_types=1);

namespace App\Tests\EventListener\Application;

use App\EventListener\Application\HostFirewallListener;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

#[CoversClass(HostFirewallListener::class)]
class HostFirewallListenerTest extends TestCase
{
    private const string JOIN_HOST = 'join.gewis.nl';
    private const string MEMBER_HOST = 'member.gewis.nl';
    private const string DATABASE_HOST = 'database.gewis.nl';

    /**
     * The register must not be reachable from the two hosts anonymous visitors are sent to, and it is refused rather
     * than redirected so the refusal says nothing about what is behind it.
     */
    #[DataProvider('pathsThatAreNotAllowed')]
    public function testRefusesWhatAHostMayNotServe(
        string $host,
        string $path,
    ): void {
        $this->expectException(NotFoundHttpException::class);

        $this->dispatch($host, $path);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function pathsThatAreNotAllowed(): array
    {
        return [
            'the register, from the sign-up host' => [self::JOIN_HOST, '/member/1'],
            'the sign-in page, from the sign-up host' => [self::JOIN_HOST, '/login'],
            'the register, from the renewal host' => [self::MEMBER_HOST, '/member/1'],
            'the renewal host has no front page' => [self::MEMBER_HOST, '/'],
            // The renewal e-mail links to /renew/{token}; the page's own route was never reachable from this host.
            'the long path to the renewal form' => [self::MEMBER_HOST, '/member/renew/some-token'],
            'the sign-up form, from the renewal host' => [self::MEMBER_HOST, '/member/subscribe'],
        ];
    }

    #[DataProvider('pathsThatAreAllowed')]
    public function testLetsThroughWhatAHostServes(
        string $host,
        string $path,
    ): void {
        $event = $this->dispatch($host, $path);

        self::assertFalse($event->hasResponse());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function pathsThatAreAllowed(): array
    {
        return [
            'the sign-up form' => [self::JOIN_HOST, '/member/subscribe'],
            'a step of the sign-up form' => [self::JOIN_HOST, '/member/subscribe/confirm'],
            'the checkout that follows it' => [self::JOIN_HOST, '/checkout/session'],
            'the renewal form' => [self::MEMBER_HOST, '/renew/some-token'],
            'what a page needs to render' => [self::JOIN_HOST, '/assets/app.css'],
            'the language switch' => [self::MEMBER_HOST, '/lang/nl_NL'],
            'robots.txt' => [self::MEMBER_HOST, '/robots.txt'],
            // The register's own host is not a boundary, and neither are the names only the stack itself uses.
            'anything on the register' => [self::DATABASE_HOST, '/member/1'],
            'a health check by container name' => ['web', '/health'],
        ];
    }

    /**
     * The address someone was given for signing up is the one they should stay on, so its root is served rather than
     * redirected.
     */
    public function testServesTheSignUpFormOnTheRootOfItsOwnHost(): void
    {
        $response = new Response('the form');
        $kernel = $this->createMock(HttpKernelInterface::class);
        $kernel->expects(self::once())
            ->method('handle')
            ->with(
                self::callback(static fn (Request $request): bool => '/member/subscribe' === $request->getPathInfo()),
                HttpKernelInterface::SUB_REQUEST,
            )
            ->willReturn($response);

        $event = $this->dispatch(self::JOIN_HOST, '/', $kernel);

        self::assertSame($response, $event->getResponse());
    }

    /**
     * The sub-request the root serves passes through this listener again; only the request that came in is judged.
     */
    public function testIgnoresSubRequests(): void
    {
        $kernel = self::createStub(HttpKernelInterface::class);
        $event = new RequestEvent(
            $kernel,
            Request::create('https://' . self::JOIN_HOST . '/member/1'),
            HttpKernelInterface::SUB_REQUEST,
        );

        new HostFirewallListener(self::JOIN_HOST, self::MEMBER_HOST, $kernel)($event);

        self::assertFalse($event->hasResponse());
    }

    private function dispatch(
        string $host,
        string $path,
        ?HttpKernelInterface $kernel = null,
    ): RequestEvent {
        $kernel ??= self::createStub(HttpKernelInterface::class);
        $event = new RequestEvent(
            $kernel,
            Request::create('https://' . $host . $path),
            HttpKernelInterface::MAIN_REQUEST,
        );

        new HostFirewallListener(self::JOIN_HOST, self::MEMBER_HOST, $kernel)($event);

        return $event;
    }
}
