<?php

declare(strict_types=1);

namespace Database\Form;

use Application\Model\Enums\MembershipTypes;
use Database\Form\Validator\BulkMemberIds as BulkMemberIdsValidator;
use Laminas\Form\Element\Radio;
use Laminas\Form\Element\Submit;
use Laminas\Form\Element\Textarea;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator;
use Override;
use RuntimeException;

use function preg_split;
use function trim;

class BulkMemberRenewal extends Form implements InputFilterProviderInterface
{
    public function __construct(private readonly Translator $translator)
    {
        parent::__construct();

        $this->add([
            'name' => 'memberIds',
            'type' => Textarea::class,
            'options' => [
                'label' => $this->translator->translate('Membership numbers'),
            ],
        ]);

        $this->add([
            'name' => 'membershipType',
            'type' => Radio::class,
            'options' => [
                'label' => $this->translator->translate('Membership Type'),
                'value_options' => [
                    // phpcs:ignore -- user-visible strings should not be split
                    MembershipTypes::Ordinary->value => $this->translator->translate('Ordinary - Enrolled at the department of M&CS'),
                    // phpcs:ignore -- user-visible strings should not be split
                    MembershipTypes::External->value => $this->translator->translate('External - Admitted by the board'),
                    // phpcs:ignore -- user-visible strings should not be split
                    MembershipTypes::Graduate->value => $this->translator->translate('Graduate - Former member admitted by the board as graduate'),
                    // phpcs:ignore -- user-visible strings should not be split
                    MembershipTypes::Honorary->value => $this->translator->translate('Honorary - Appointed by the GMM'),
                ],
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => $this->translator->translate('Preview changes'),
            ],
        ]);

        $this->add([
            'name' => 'intent',
            'type' => Submit::class,
            'attributes' => [
                'value' => 'preview',
            ],
        ]);
    }

    /**
     * @return array<string, array{
     *     required?: bool,
     *     validators?: array<int, array{name: class-string, options?: array<string, mixed>}>,
     * }>
     */
    #[Override]
    public function getInputFilterSpecification(): array
    {
        return [
            'memberIds' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => BulkMemberIdsValidator::class,
                    ],
                ],
            ],
            'membershipType' => [
                'required' => true,
            ],
        ];
    }

    /**
     * @return int[]
     */
    public function getParsedMemberIds(): array
    {
        // After this check we are ensured a unique list of numeric IDs by BulkMemberIdsValidator
        if (!$this->isValid()) {
            throw new RuntimeException('Cannot parse member IDs from invalid form.');
        }

        $rawMemberIds = trim((string) $this->get('memberIds')->getValue());
        $tokens = preg_split('/[\s,;]+/', $rawMemberIds) ?: [];
        $memberIds = [];

        foreach ($tokens as $token) {
            if ('' === $token) {
                continue;
            }

            $memberIds[] = (int) $token;
        }

        return $memberIds;
    }
}
