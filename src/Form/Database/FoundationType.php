<?php

declare(strict_types=1);

namespace App\Form\Database;

use App\Entity\Database\Enums\OrganTypes;
use App\Form\Database\DataMapper\FoundationMapper;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

use function Symfony\Component\Translation\t;

class FoundationType extends AbstractType
{
    public function __construct(private readonly FoundationMapper $dataMapper)
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
                'type',
                EnumType::class,
                [
                    'label' => t('Type'),
                    'class' => OrganTypes::class,
                    'expanded' => true,
                    'placeholder' => false,
                    'constraints' => [new NotNull()],
                ],
            )
            ->add(
                'name',
                TextType::class,
                [
                    'label' => t('Name'),
                    'constraints' => [
                        new NotBlank(),
                        new Length(
                            min: 2,
                            max: 128,
                        ),
                    ],
                ],
            )
            ->add(
                'abbr',
                TextType::class,
                [
                    'label' => t('Abbreviation'),
                    'constraints' => [
                        new NotBlank(),
                        new Length(
                            min: 2,
                            max: 32,
                        ),
                    ],
                ],
            )
            ->add(
                'members',
                CollectionType::class,
                [
                    'label' => t('Members'),
                    'entry_type' => MemberFunctionType::class,
                    'allow_add' => true,
                    'allow_delete' => true,
                    'prototype' => true,
                    'prototype_name' => '__index__',
                    // The page opens with two blank rows to fill in; further ones are added from the prototype.
                    'data' => [
                        [],
                        [],
                    ],
                    'constraints' => [new Count(min: 1)],
                ],
            )
            ->add(
                'submit',
                SubmitType::class,
                ['label' => t('Found Body')],
            )
            ->setDataMapper($this->dataMapper);
    }

    #[Override]
    public function getParent(): string
    {
        return BaseDecisionType::class;
    }
}
