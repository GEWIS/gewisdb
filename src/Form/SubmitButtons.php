<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\ClickableInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Which of a form's submit buttons was pressed.
 *
 * A form with more than one submit button decides different things depending on which one was used — confirming a
 * deletion, saving instead of previewing. `FormInterface` says nothing about being clickable and `getClickedButton()`
 * lives on the concrete `Form`, so the narrowing happens here rather than in every controller that asks.
 */
final class SubmitButtons
{
    public static function clicked(
        FormInterface $form,
        string $name,
    ): bool {
        if (!$form->has($name)) {
            return false;
        }

        $button = $form->get($name);

        return $button instanceof ClickableInterface
            && $button->isClicked();
    }
}
