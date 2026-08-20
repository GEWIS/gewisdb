<?php

declare(strict_types=1);

namespace App\Entity\Application\Enums;

use Override;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * How severe a message to the user is, as GEWISWEB names it.
 *
 * The cases are Bootstrap's contextual names, which is what lets a toast turn a level straight into a `bg-*` utility,
 * and they are the labels `addFlash()` is called with, so a flash needs no mapping on its way to the screen.
 */
enum AlertTypes: string implements TranslatableInterface
{
    case Success = 'success';
    case Danger = 'danger';
    case Warning = 'warning';
    case Info = 'info';

    /**
     * The level's name, deferred so the caller decides on the locale (or takes the source string).
     */
    public function getName(): TranslatableMessage
    {
        return match ($this) {
            self::Success => new TranslatableMessage('Success'),
            self::Danger => new TranslatableMessage('Danger'),
            self::Warning => new TranslatableMessage('Warning'),
            self::Info => new TranslatableMessage('Information'),
        };
    }

    #[Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        return $this->getName()->trans(
            $translator,
            $locale,
        );
    }
}
