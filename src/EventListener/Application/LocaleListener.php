<?php

declare(strict_types=1);

namespace App\EventListener\Application;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

use function in_array;
use function is_string;

/**
 * Applies the language chosen through /lang/{lang}, which the controller stores on the session.
 *
 * Symfony does not read the session for this, so without a listener the stored value is written and never used.
 * Priority 20 runs before Symfony's own LocaleListener, so a route that carries an explicit `_locale` still wins.
 */
#[AsEventListener(
    event: KernelEvents::REQUEST,
    priority: 20,
)]
final readonly class LocaleListener
{
    /**
     * @param string[] $enabledLocales
     */
    public function __construct(
        private array $enabledLocales,
        private string $defaultLocale,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$request->hasPreviousSession()) {
            return;
        }

        $locale = $request->getSession()->get('_locale');

        $request->setLocale(
            is_string($locale) && in_array(
                $locale,
                $this->enabledLocales,
                true,
            )
                ? $locale
                : $this->defaultLocale,
        );
    }
}
