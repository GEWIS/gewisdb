<?php

declare(strict_types=1);

namespace App\Form\Database;

use App\Entity\Database\ListmonkMailingList;
use App\Entity\Database\MailingList;
use App\Entity\Database\MailmanMailingList;
use Override;
use ReflectionProperty;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use function sprintf;
use function Symfony\Component\Translation\t;

/**
 * Form to create and edit a mailing list.
 *
 * Which Mailman and Listmonk lists may be picked is decided by the caller (a list that is already bound to another
 * mailing list must not be offered again) and passed in through the `mailman_lists` and `listmonk_lists` options.
 */
class MailingListType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder->add(
            'name',
            TextType::class,
            [
                'label' => t('Name'),
                'required' => true,
                'empty_data' => '',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(
                        min: 2,
                        max: 64,
                    ),
                ],
            ],
        );

        $builder->add(
            'nl_description',
            TextareaType::class,
            [
                'label' => t('Dutch Description'),
                'required' => true,
                'empty_data' => '',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(min: 10),
                ],
            ],
        );

        $builder->add(
            'en_description',
            TextareaType::class,
            [
                'label' => t('English Description'),
                'required' => true,
                'empty_data' => '',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(min: 10),
                ],
            ],
        );

        $builder->add(
            'onForm',
            CheckboxType::class,
            [
                'label' => t('On Form'),
                'required' => false,
            ],
        );

        $builder->add(
            'defaultSub',
            CheckboxType::class,
            [
                'label' => t('Auto-subscription'),
                'required' => false,
            ],
        );

        $builder->add(
            'mailmanList',
            EntityType::class,
            [
                'label' => t('Mailman Mailing List'),
                'class' => MailmanMailingList::class,
                'choices' => $options['mailman_lists'],
                'choice_label' => static function (MailmanMailingList $mailmanList): string {
                    return sprintf(
                        '%s (%s)',
                        $mailmanList->getName(),
                        $mailmanList->getMailmanId(),
                    );
                },
                'placeholder' => t('Choose a mailing list'),
                'required' => false,
            ],
        );

        $builder->add(
            'listmonkList',
            EntityType::class,
            [
                'label' => t('Listmonk Mailing List'),
                'class' => ListmonkMailingList::class,
                'choices' => $options['listmonk_lists'],
                'choice_label' => static function (ListmonkMailingList $listmonkList): string {
                    return sprintf(
                        '%s (%s)',
                        $listmonkList->getName(),
                        $listmonkList->getListmonkId(),
                    );
                },
                'placeholder' => t('Choose a mailing list'),
                'required' => false,
            ],
        );

        $builder->add(
            'submit',
            SubmitType::class,
            ['label' => t('Add Mailing List')],
        );

        // A list that does not exist yet is shown on the join form unless that is unchecked. The entity carries no
        // default for this, so it is applied here before the checkbox reads its value.
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            static function (FormEvent $event): void {
                $list = $event->getData();

                if (
                    !$list instanceof MailingList
                    || new ReflectionProperty(MailingList::class, 'onForm')->isInitialized($list)
                ) {
                    return;
                }

                $list->setOnForm(true);
            },
        );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MailingList::class,
            'mailman_lists' => [],
            'listmonk_lists' => [],
            'constraints' => [new Assert\Callback(self::validateSingleExternalList(...))],
        ]);

        $resolver->setAllowedTypes(
            'mailman_lists',
            MailmanMailingList::class . '[]',
        );
        $resolver->setAllowedTypes(
            'listmonk_lists',
            ListmonkMailingList::class . '[]',
        );
    }

    /**
     * A mailing list is synchronised to at most one external system.
     */
    public static function validateSingleExternalList(
        ?MailingList $list,
        ExecutionContextInterface $context,
    ): void {
        if (
            null === $list
            || !$list->hasMailmanList()
            || !$list->hasListmonkList()
        ) {
            return;
        }

        $context->buildViolation('Mailman and Listmonk mailing lists cannot both be set at the same time')
            ->atPath('listmonkList')
            ->addViolation();
    }
}
