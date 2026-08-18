<?php

declare(strict_types=1);

namespace App\Form\Database;

use App\Entity\Database\MailingList;
use Override;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Label of a mailing list checkbox on the registration form.
 *
 * The description is not a translatable string but a pair of columns on the list itself, so which one to show can
 * only be decided once the locale the form renders in is known.
 */
final readonly class MailingListLabel implements TranslatableInterface
{
    public function __construct(private MailingList $list)
    {
    }

    #[Override]
    public function trans(
        TranslatorInterface $translator,
        ?string $locale = null,
    ): string {
        $description = 'en' === ($locale ?? $translator->getLocale())
            ? $this->list->getEnDescription()
            : $this->list->getNlDescription();

        return '<strong>' . $this->list->getName() . '</strong> ' . $description;
    }
}
