<?php

declare(strict_types=1);

namespace App\Form\Database;

use App\Entity\Database\Enums\MeetingTypes;
use App\Form\DataTransformer\DecisionLookupTransformer;
use App\Form\DataTransformer\StringToEnumTransformer;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Refers to an existing decision, such as the one an annulment takes back.
 *
 * The data of this type is the decision itself.
 */
class DecisionType extends AbstractType
{
    public function __construct(private readonly DecisionLookupTransformer $decisionLookupTransformer)
    {
    }

    /**
     * @param array<string, mixed> $options
     */
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder->add(
            'meeting_type',
            HiddenType::class,
        );
        $builder->add(
            'meeting_number',
            HiddenType::class,
        );
        $builder->add(
            'point',
            HiddenType::class,
        );
        $builder->add(
            'number',
            HiddenType::class,
        );

        $builder->get('meeting_type')->addModelTransformer(new StringToEnumTransformer(MeetingTypes::class));

        $builder->addModelTransformer($this->decisionLookupTransformer);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'invalid_message' => 'Select an existing decision.',
        ]);
    }
}
