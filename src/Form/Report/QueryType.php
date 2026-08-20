<?php

declare(strict_types=1);

namespace App\Form\Report;

use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

use function Symfony\Component\Translation\t;

/**
 * Form for the DQL that is run against ReportDB.
 */
class QueryType extends AbstractType
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
            'query',
            TextareaType::class,
            [
                'label' => t('Query'),
                'required' => true,
                // The submitted value is executed as DQL, so it must reach the service byte for byte as it was typed.
                'trim' => false,
                'empty_data' => '',
                'constraints' => [new Assert\NotBlank()],
            ],
        );

        $builder->add(
            'submit',
            SubmitType::class,
            ['label' => t('Execute')],
        );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null]);
    }
}
