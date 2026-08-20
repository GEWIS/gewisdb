<?php

declare(strict_types=1);

namespace App\Form\Database;

use App\Entity\Database\Enums\MeetingTypes;
use App\Form\DataTransformer\MeetingLookupTransformer;
use App\Form\DataTransformer\StringToEnumTransformer;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Refers to the meeting in which a decision is taken.
 *
 * The data of this type is the meeting itself.
 */
class MeetingType extends AbstractType
{
    public function __construct(private readonly MeetingLookupTransformer $meetingLookupTransformer)
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
            'type',
            HiddenType::class,
        );
        $builder->add(
            'number',
            HiddenType::class,
        );
        // Rendered so the templates and the key code date checks have the meeting date at hand without another
        // lookup. It is not part of the meeting's identity and is never written back.
        $builder->add(
            'date',
            HiddenType::class,
        );

        $builder->get('type')->addModelTransformer(new StringToEnumTransformer(MeetingTypes::class));

        $builder->addModelTransformer($this->meetingLookupTransformer);
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'invalid_message' => 'Select an existing meeting.',
        ]);
    }
}
