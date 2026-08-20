<?php

declare(strict_types=1);

namespace App\Form\Report;

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ClickableInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

use function Symfony\Component\Translation\t;

/**
 * Form for storing a query under a name, on top of the query itself.
 *
 * The form yields an array; turning that into a `SavedQuery` (looking up an existing query by name, or creating a new
 * one) is done by `App\Service\Report\QueryService::save()`.
 */
class QuerySaveType extends AbstractType
{
    /**
     * Validation group for the constraints that only apply when the query is being stored.
     */
    public const string SAVE_GROUP = 'save';

    /**
     * @param array<string, mixed> $options
     */
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder->add(
            'category',
            TextType::class,
            [
                'label' => t('Category'),
                'required' => false,
                'empty_data' => '',
            ],
        );

        // Not `required`: the browser would refuse to submit the form until it was filled in, and the other button
        // on this form runs the query without saving it. Naming is demanded when saving, by the constraint below.
        $builder->add(
            'name',
            TextType::class,
            [
                'label' => t('Name'),
                'required' => false,
                'empty_data' => '',
                'constraints' => [new Assert\NotBlank(groups: [self::SAVE_GROUP])],
            ],
        );

        $builder->add(
            'submit_save',
            SubmitType::class,
            ['label' => t('Save')],
        );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Executing and saving are the two buttons of one form, so the name is only demanded when it was the
            // save button that was clicked; running a query does not require naming it first.
            'validation_groups' => static function (FormInterface $form): array {
                $save = $form->get('submit_save');

                return $save instanceof ClickableInterface && $save->isClicked()
                    ? [
                        'Default',
                        self::SAVE_GROUP,
                    ]
                    : ['Default'];
            },
        ]);
    }

    #[Override]
    public function getParent(): string
    {
        return QueryType::class;
    }
}
