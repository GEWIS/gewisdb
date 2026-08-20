<?php

declare(strict_types=1);

namespace App\Form\Database;

use App\Entity\Database\SubDecision\Foundation;
use App\Form\Database\DataMapper\AbolishMapper;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

use function Symfony\Component\Translation\t;

class AbolishType extends AbstractType
{
    public function __construct(private readonly AbolishMapper $dataMapper)
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
            // Only the source for the organ autocomplete, which fills in the foundation reference below.
            ->add(
                'name',
                TextType::class,
                [
                    'label' => t('Body'),
                    'mapped' => false,
                    'required' => false,
                ],
            )
            ->add(
                'subdecision',
                SubDecisionType::class,
                ['subdecision_class' => Foundation::class],
            )
            ->add(
                'submit',
                SubmitType::class,
                ['label' => t('Abolish Body')],
            )
            ->setDataMapper($this->dataMapper);
    }

    #[Override]
    public function getParent(): string
    {
        return BaseDecisionType::class;
    }
}
