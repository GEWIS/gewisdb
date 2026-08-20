<?php

declare(strict_types=1);

namespace App\Form\Report;

use App\Repository\Database\MeetingRepository;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

use function strtoupper;
use function Symfony\Component\Translation\t;

/**
 * Form to select the meetings whose decisions are exported.
 */
class ExportType extends AbstractType
{
    public function __construct(private readonly MeetingRepository $meetingRepository)
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
            'meetings',
            ChoiceType::class,
            [
                'label' => t('Meetings'),
                // A meeting is identified by its type and number, which is how the export service takes it apart again.
                'choices' => $this->getChoices(),
                'choice_translation_domain' => false,
                'multiple' => true,
                'required' => true,
                'constraints' => [new Assert\NotBlank()],
            ],
        );

        $builder->add(
            'submit',
            SubmitType::class,
            ['label' => t('Export Decisions')],
        );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null]);
    }

    /**
     * @return array<string, string>
     */
    private function getChoices(): array
    {
        $choices = [];

        foreach ($this->meetingRepository->findAllWithDecisionCount() as $result) {
            $meeting = $result[0];
            $label = strtoupper($meeting->getType()->value) . ' ' . $meeting->getNumber()
                   . '   (' . $meeting->getDate()->format('j F Y') . ')';

            $choices[$label] = $meeting->getType()->value . '-' . $meeting->getNumber();
        }

        return $choices;
    }
}
