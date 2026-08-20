<?php

declare(strict_types=1);

namespace App\Form\Database;

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ClickableInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function Symfony\Component\Translation\t;

/**
 * Confirmation form for deleting a mailing list.
 */
class DeleteListType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder->add(
            'submit_yes',
            SubmitType::class,
            ['label' => t('Yes')],
        );
        $builder->add(
            'submit_no',
            SubmitType::class,
            ['label' => t('No')],
        );

        // Only the "Yes" button confirms the deletion. Any other submission leaves the form invalid, so that a caller
        // that merely checks `isValid()` cannot delete a list on a "No".
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            static function (FormEvent $event): void {
                $form = $event->getForm();
                $confirmation = $form->get('submit_yes');

                if (
                    $confirmation instanceof ClickableInterface
                    && $confirmation->isClicked()
                ) {
                    return;
                }

                $form->addError(new FormError('Confirmation is required.'));
            },
        );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null]);
    }
}
