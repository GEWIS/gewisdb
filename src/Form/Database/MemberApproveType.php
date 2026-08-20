<?php

declare(strict_types=1);

namespace App\Form\Database;

use Override;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

use function Symfony\Component\Translation\t;

/**
 * Approving a prospective member picks the membership type they start on.
 *
 * 2026-05: Removed tueData from approve form. Class kept to allow for manual process implementation.
 */
class MemberApproveType extends MembershipTypeType
{
    /**
     * @param array<string, mixed> $options
     */
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        parent::buildForm(
            $builder,
            $options,
        );

        // A membership that is only now beginning has no period a change could fall in, so the inherited date is not
        // asked for. It is required where it does apply, and would reject the form here.
        $builder->remove('changeDate');
        $builder->add(
            'submit',
            SubmitType::class,
            ['label' => t('Approve Membership')],
        );
    }
}
