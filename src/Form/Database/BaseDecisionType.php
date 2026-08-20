<?php

declare(strict_types=1);

namespace App\Form\Database;

use App\Entity\Database\Decision;
use App\Entity\Database\Meeting;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * What every decision form has in common: which meeting the decision is taken in, and where in that meeting.
 *
 * This is a parent type rather than a PHP base class. Every decision form needs exactly one data mapper of its own,
 * so there is nothing to inherit beyond these three fields and `data_class`, and expressing that through
 * {@see AbstractType::getParent()} keeps each form a plain autowired type instead of one that has to pass the base's
 * dependencies up a constructor chain. It also puts `base_decision` in every decision form's block prefix chain, so
 * a single form theme block covers the hidden meeting reference on all of them.
 *
 * The meeting, point and decision number are options rather than pre-set form data, because the page that shows all
 * decision forms at once knows them, whereas the endpoint that receives one back only has what was posted.
 */
class BaseDecisionType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $meetingOptions = [];

        if (null !== $options['meeting']) {
            $meetingOptions['data'] = $options['meeting'];
        }

        $pointOptions = [];

        if (null !== $options['point']) {
            $pointOptions['data'] = (string) $options['point'];
        }

        $numberOptions = [];

        if (null !== $options['number']) {
            $numberOptions['data'] = (string) $options['number'];
        }

        $builder
            ->add(
                'meeting',
                MeetingType::class,
                $meetingOptions,
            )
            ->add(
                'point',
                HiddenType::class,
                $pointOptions,
            )
            ->add(
                'decision',
                HiddenType::class,
                $numberOptions,
            );
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Decision::class,
            'meeting' => null,
            'point' => null,
            'number' => null,
        ]);

        $resolver->setAllowedTypes(
            'meeting',
            [
                'null',
                Meeting::class,
            ],
        );
        $resolver->setAllowedTypes(
            'point',
            [
                'null',
                'int',
            ],
        );
        $resolver->setAllowedTypes(
            'number',
            [
                'null',
                'int',
            ],
        );
    }
}
