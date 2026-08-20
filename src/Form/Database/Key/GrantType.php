<?php

declare(strict_types=1);

namespace App\Form\Database\Key;

use App\Entity\Application\AssociationYear;
use App\Entity\Database\Meeting;
use App\Form\Database\BaseDecisionType;
use App\Form\Database\DataMapper\Key\GrantMapper;
use App\Form\Database\MemberLookupType;
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

class GrantType extends AbstractType
{
    public function __construct(private readonly GrantMapper $dataMapper)
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
                'grantee',
                MemberLookupType::class,
                ['label' => t('Grantee')],
            )
            ->add(
                'until',
                DateType::class,
                [
                    'label' => t('Date of Expiration'),
                    'widget' => 'single_text',
                    'constraints' => [
                        new NotNull(),
                        new Callback([self::class, 'validateNotInThePast']),
                        new Callback([self::class, 'validateNotTooFar']),
                    ],
                ],
            )
            ->add(
                'submit',
                SubmitType::class,
                ['label' => t('Grant Key Code')],
            )
            ->setDataMapper($this->dataMapper);
    }

    #[Override]
    public function getParent(): string
    {
        return BaseDecisionType::class;
    }

    /**
     * A key code cannot be handed out with an expiry that has already passed by the time the decision is taken.
     */
    public static function validateNotInThePast(
        mixed $value,
        ExecutionContextInterface $context,
    ): void {
        $meeting = self::meeting($context);

        if (
            !$value instanceof DateTimeInterface
            || null === $meeting
            || $value >= $meeting->getDate()
        ) {
            return;
        }

        $context->buildViolation('Key code cannot be granted in the past.')->addViolation();
    }

    /**
     * A key code lasts at most until the start of the association year after the one it is granted in.
     */
    public static function validateNotTooFar(
        mixed $value,
        ExecutionContextInterface $context,
    ): void {
        $meeting = self::meeting($context);

        if (
            !$value instanceof DateTimeInterface
            || null === $meeting
        ) {
            return;
        }

        $limit = AssociationYear::fromDate($meeting->getDate())->septemberFirst();

        if ($value <= $limit) {
            return;
        }

        $context->buildViolation(
            'Key code cannot be granted after September 1st of the next association year.',
        )->addViolation();
    }

    private static function meeting(ExecutionContextInterface $context): ?Meeting
    {
        $root = $context->getRoot();

        if (!$root instanceof FormInterface) {
            return null;
        }

        $meeting = $root->get('meeting')->getData();

        return $meeting instanceof Meeting
            ? $meeting
            : null;
    }
}
