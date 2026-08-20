<?php

declare(strict_types=1);

namespace App\Form\Database;

use App\Entity\Database\SubDecision\Foundation;
use App\Entity\Database\SubDecision\Installation;
use App\Form\Database\DataMapper\InstallMapper;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

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
            // All three collections start out empty: the page builds their rows from the prototype as the user works
            // through the organ's current membership.
            ->add(
                'installations',
                CollectionType::class,
                [
                    'label' => t('Installations'),
                    'entry_type' => InstallationType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'prototype' => true,
                    'prototype_name' => '__index__',
                ],
            )
            ->add(
                'reappointments',
                CollectionType::class,
                [
                    'label' => t('Reappointments'),
                    'entry_type' => SubDecisionType::class,
                    'entry_options' => ['subdecision_class' => Installation::class],
                    'allow_add' => true,
                    'allow_delete' => true,
                    'prototype' => true,
                    'prototype_name' => '__index__',
                ],
            )
            ->add(
                'discharges',
                CollectionType::class,
                [
                    'label' => t('Discharges'),
                    'entry_type' => SubDecisionType::class,
                    'entry_options' => ['subdecision_class' => Installation::class],
                    'allow_add' => true,
                    'allow_delete' => true,
                    'prototype' => true,
                    'prototype_name' => '__index__',
                ],
            )
            ->add(
                'submit',
                SubmitType::class,
                ['label' => t('Confirm Changes')],
            )
            ->setDataMapper($this->dataMapper);
    }

    #[Override]
    public function getParent(): string
    {
        return BaseDecisionType::class;
    }
}
