<?php

declare(strict_types=1);

namespace App\Form\Database;

use App\Entity\Database\AuditNote;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

use function Symfony\Component\Translation\t;

/**
 * A note the secretary leaves on a member. Who left it and on whom is not part of the form; the caller sets both.
 */
class AuditNoteType extends AbstractType
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
            'note',
            TextType::class,
            [
                'label' => t('Note'),
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(
                        min: 2,
                        max: 255,
                    ),
                ],
            ],
        );

        $builder->add(
            'submit',
            SubmitType::class,
            ['label' => t('Leave note')],
        );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => AuditNote::class]);
    }
}
