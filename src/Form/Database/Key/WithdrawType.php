<?php

declare(strict_types=1);

namespace App\Form\Database\Key;

use App\Entity\Database\Meeting;
use App\Entity\Database\SubDecision\Key\Granting;
use App\Form\Database\BaseDecisionType;
use App\Form\Database\DataMapper\Key\WithdrawMapper;
use App\Form\Database\GrantingType;
use App\Form\Database\SubDecisionType;
use DateTimeInterface;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use function Symfony\Component\Translation\t;

class WithdrawType extends AbstractType
{
    public function __construct(private readonly WithdrawMapper $dataMapper)
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
                ['subdecision_class' => Granting::class],
            )
            // The granting's member and expiry, shown alongside the date being picked. The withdrawal itself is
            // built from the granting the reference above resolves to, so nothing is read back off these.
            ->add(
                'granting',
                GrantingType::class,
                ['mapped' => false],
            )
            ->add(
                'withdrawOn',
                DateType::class,
                [
                    'label' => t('Effective From'),
                    'widget' => 'single_text',
                    'constraints' => [
                        new NotNull(),
                        new Callback([self::class, 'validateNotInThePast']),
                        new Callback([self::class, 'validateNotAfterGranting']),
                    ],
                ],
            )
            ->add(
                'submit',
                SubmitType::class,
                ['label' => t('Withdraw Key Code')],
            )
            ->setDataMapper($this->dataMapper);
    }

    #[Override]
    public function getParent(): string
    {
        return BaseDecisionType::class;
    }

    /**
     * A key code cannot be taken back before the decision that takes it back was made.
     */
    public static function validateNotInThePast(
        mixed $value,
        ExecutionContextInterface $context,
    ): void {
        $root = self::root($context);
        $meeting = $root?->get('meeting')->getData();

        if (
            !$value instanceof DateTimeInterface
            || !$meeting instanceof Meeting
            || $value >= $meeting->getDate()
        ) {
            return;
        }

        $context->buildViolation('Key code cannot be withdrawn in the past.')->addViolation();
    }

    /**
     * Withdrawing a key code after it has already expired would record something that never happened.
     */
    public static function validateNotAfterGranting(
        mixed $value,
        ExecutionContextInterface $context,
    ): void {
        $root = self::root($context);
        $granting = $root?->get('subdecision')->getData();

        if (
            !$value instanceof DateTimeInterface
            || !$granting instanceof Granting
            || $value <= $granting->getUntil()
        ) {
            return;
        }

        $context->buildViolation('Key code cannot be withdrawn after its original expiration.')->addViolation();
    }

    private static function root(ExecutionContextInterface $context): ?FormInterface
    {
        $root = $context->getRoot();

        return $root instanceof FormInterface
            ? $root
            : null;
    }
}
