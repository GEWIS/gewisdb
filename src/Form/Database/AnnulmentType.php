<?php

declare(strict_types=1);

namespace App\Form\Database;

use App\Form\Database\DataMapper\AnnulmentMapper;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

use function Symfony\Component\Translation\t;

class AnnulmentType extends AbstractType
{
    public function __construct(private readonly AnnulmentMapper $dataMapper)
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
        $builder
            // Only the source for the decision autocomplete, which fills in the reference below.
            ->add(
                'name',
                TextType::class,
                [
                    'label' => t('Decision'),
                    'mapped' => false,
                    'required' => false,
                ],
            )
            ->add(
                'fdecision',
                DecisionType::class,
            )
            ->add(
                'submit',
                SubmitType::class,
                ['label' => t('Annul Decision')],
            )
            ->setDataMapper($this->dataMapper);
    }

    #[Override]
    public function getParent(): string
    {
        return BaseDecisionType::class;
    }
}
