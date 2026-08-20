<?php

declare(strict_types=1);

namespace App\Form\Database\Board;

use App\Entity\Database\Enums\BoardFunctions;
use App\Form\Database\BaseDecisionType;
use App\Form\Database\DataMapper\Board\InstallMapper;
use App\Form\Database\MemberLookupType;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotNull;

use function array_filter;
use function array_values;
use function Symfony\Component\Translation\t;

class InstallType extends AbstractType
{
    public function __construct(private readonly InstallMapper $dataMapper)
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
        // Legacy board functions describe boards that have been, not boards being installed now.
        $current = array_values(array_filter(
            BoardFunctions::cases(),
            static fn (BoardFunctions $function): bool => !$function->isLegacy(),
        ));

        $builder
            ->add(
                'member',
                MemberLookupType::class,
            )
            ->add(
                'function',
                EnumType::class,
                [
                    'label' => t('Function'),
                    'class' => BoardFunctions::class,
                    'choices' => $current,
                    'placeholder' => t('Please select a function'),
                    'constraints' => [new NotNull()],
                ],
            )
            ->add(
                'date',
                DateType::class,
                [
                    'label' => t('Effective From'),
                    'widget' => 'single_text',
                    'constraints' => [new NotNull()],
                ],
            )
            ->add(
                'submit',
                SubmitType::class,
                ['label' => t('Install Board Member')],
            )
            ->setDataMapper($this->dataMapper);
    }

    #[Override]
    public function getParent(): string
    {
        return BaseDecisionType::class;
    }
}
