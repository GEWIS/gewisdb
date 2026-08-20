<?php

declare(strict_types=1);

namespace App\Form\Database;

use App\Entity\Database\Enums\MeetingTypes;
use App\Entity\Database\SubDecision;
use App\Form\DataTransformer\StringToEnumTransformer;
use App\Form\DataTransformer\SubDecisionLookupTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function is_subclass_of;

/**
 * Refers to an existing subdecision, such as the foundation an organ is installed into.
 *
 * The data of this type is the subdecision itself. Set `subdecision_class` to the kind of subdecision the embedding
 * form points at (the foundation of an organ, the installation being discharged, the key code being withdrawn, ...);
 * it is what the lookup is narrowed to.
 */
class SubDecisionType extends AbstractType
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
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
            'decision_point',
            HiddenType::class,
        );
        $builder->add(
            'decision_number',
            HiddenType::class,
        );
        $builder->add(
            'sequence',
            HiddenType::class,
        );

        $builder->get('meeting_type')->addModelTransformer(new StringToEnumTransformer(MeetingTypes::class));

        $builder->addModelTransformer(new SubDecisionLookupTransformer(
            $this->entityManager,
            $options['subdecision_class'],
        ));
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'subdecision_class' => SubDecision::class,
            'invalid_message' => 'Select an existing subdecision.',
        ]);

        $resolver->setAllowedTypes(
            'subdecision_class',
            'string',
        );
        $resolver->setAllowedValues(
            'subdecision_class',
            static function (string $subDecisionClass): bool {
                return SubDecision::class === $subDecisionClass
                    || is_subclass_of(
                        $subDecisionClass,
                        SubDecision::class,
                    );
            },
        );
    }
}
