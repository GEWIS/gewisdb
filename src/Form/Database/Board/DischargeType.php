<?php

declare(strict_types=1);

namespace App\Form\Database\Board;

use App\Entity\Database\SubDecision\Board\Installation;
use App\Form\Database\BaseDecisionType;
use App\Form\Database\DataMapper\Board\DischargeMapper;
use App\Form\Database\SubDecisionType;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

use function Symfony\Component\Translation\t;

class DischargeType extends AbstractType
{
    public function __construct(private readonly DischargeMapper $dataMapper)
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
            ->add(
                'subdecision',
                SubDecisionType::class,
                ['subdecision_class' => Installation::class],
            )
            ->add(
                'submit',
                SubmitType::class,
                ['label' => t('Discharge Board Member')],
            )
            ->setDataMapper($this->dataMapper);
    }

    #[Override]
    public function getParent(): string
    {
        return BaseDecisionType::class;
    }
}
