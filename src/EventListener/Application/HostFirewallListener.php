<?php

declare(strict_types=1);

namespace App\EventListener\Application;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

use function preg_match;

/**
 * What each public host is allowed to serve.
 *
 * The application answers on three names. Two of them are for people who are not signed in — someone joining, and a
 * graduate renewing — and they must not expose the register behind them: before this, nginx gave each name an
 * allowlist of paths and refused everything else with a 404. That allowlist went with the nginx stack, so it is here
 * now, and it refuses the same way rather than redirecting, because a redirect would tell an anonymous visitor that
 * the page exists.
 *
 * A name that is not one of the three is unrestricted: that is `localhost` in development and the container name in
 * a health check, neither of which is a public boundary.
 */
// Above RouterListener's 32 rather than equal to it. At the same priority the two are ordered by whichever was
// registered first, which nothing here declares and an added bundle or a Symfony upgrade can reverse; running after the
// router would mean the request already carries the `_controller` of the page the host is not allowed to reach.
#[AsEventListener(
    event: KernelEvents::REQUEST,
    priority: 33,
)]
final readonly class HostFirewallListener
{
    /**
     * What may be reached on the sign-up host: the form, its checkout, and what a page needs to render.
     */
    private const array JOIN_PATHS = [
        '#^/member/subscribe(/|$)#',
        '#^/checkout/#',
    ];

    /**
     * What may be reached on the renewal host: the renewal form, under the short path only.
     *
     * `/member/renew/{token}` is refused here even though it is the same page. That is the boundary as it was: nginx
     * allowlisted `/renew/` and rewrote it inward, so the long path was never reachable from this name, and the link
     * in the renewal e-mail is the short one.
     */
    private const array MEMBER_PATHS = [
        '#^/renew/#',
    ];

    /**
     * Reachable on every host, because a page that is allowed has to be able to look like one.
     */
    private const array SHARED_PATHS = [
        '#^/assets/#',
        '#^/(js|css|img|fonts)/#',
        '#^/lang/[a-zA-Z_]{2,5}$#',
        '#^/robots\.txt$#',
        '#^/\.well-known/#',
    ];

    public function __construct(
        #[Autowire(env: 'JOIN_HOSTNAME')]
        private string $joinHost,
        #[Autowire(env: 'MEMBER_HOSTNAME')]
        private string $memberHost,
        private HttpKernelInterface $kernel,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $host = $request->getHost();
        $path = $request->getPathInfo();

        if ($this->joinHost === $host) {
            // The sign-up host answers its root with the form, which is what nginx rewrote it to. A redirect would
            // do instead, but the address someone was given is the one they should stay on.
            if ('/' === $path) {
                $event->setResponse($this->serve($request, '/member/subscribe'));

                return;
            }

            $this->refuseUnless(
                $path,
                self::JOIN_PATHS,
            );

            return;
        }

        if ($this->memberHost !== $host) {
            return;
        }

        $this->refuseUnless(
            $path,
            self::MEMBER_PATHS,
        );
    }

    /**
     * @param string[] $allowed
     */
    private function refuseUnless(
        string $path,
        array $allowed,
    ): void {
        foreach ([...$allowed, ...self::SHARED_PATHS] as $pattern) {
            if (
                1 === preg_match(
                    $pattern,
                    $path,
                )
            ) {
                return;
            }
        }

        throw new NotFoundHttpException();
    }

    private function serve(
        Request $request,
        string $path,
    ): mixed {
        $server = $request->server->all();
        $server['REQUEST_URI'] = $path;

        // Empty attributes rather than `null`: `null` keeps a clone of the parent's, and a sub-request that already
        // has a `_controller` is one the router refuses to touch, so it would serve the parent's route instead of the
        // path asked for here.
        return $this->kernel->handle(
            $request->duplicate(
                null,
                null,
                [],
                null,
                null,
                $server,
            ),
            HttpKernelInterface::SUB_REQUEST,
        );
    }
}
