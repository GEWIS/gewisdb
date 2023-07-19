<?php

declare(strict_types=1);

namespace Database\Form;

use Application\Model\Enums\AddressTypes;
use Database\Form\Fieldset\Address as AddressFieldset;
use Database\Model\MailingList as MailingListModel;
use DateInterval;
use DateTime;
use Laminas\Filter\StringToLower;
use Laminas\Filter\ToNull;
use Laminas\Form\Element\Checkbox;
use Laminas\Form\Element\Date;
use Laminas\Form\Element\Email;
use Laminas\Form\Element\Select;
use Laminas\Form\Element\Submit;
use Laminas\Form\Element\Text;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\Validator\Callback;
use Laminas\Validator\Identical;
use Laminas\Validator\Regex;
use Laminas\Validator\StringLength;
use Throwable;

class Member extends Form implements InputFilterProviderInterface
{
    /** @var MailingListModel[] $lists */
    protected array $lists;

    public function __construct(
        AddressFieldset $address,
        protected readonly MvcTranslator $translator,
    ) {
        parent::__construct();

        $this->add([
            'name' => 'lastName',
            'type' => Text::class,
            'options' => [
                'label' => $translator->translate('Last Name'),
            ],
        ]);

        $this->add([
            'name' => 'middleName',
            'type' => Text::class,
            'options' => [
                'label' => $translator->translate('Last Name Prepositional Particle'),
            ],
        ]);

        $this->add([
            'name' => 'initials',
            'type' => Text::class,
            'options' => [
                'label' => $translator->translate('Initial(s)'),
            ],
        ]);

        $this->add([
            'name' => 'firstName',
            'type' => Text::class,
            'options' => [
                'label' => $translator->translate('First Name'),
            ],
        ]);

        $this->add([
            'name' => 'tueUsername',
            'type' => Text::class,
            'options' => [
                'label' => $translator->translate('TU/e-username'),
            ],
        ]);

        $this->add([
            'name' => 'study',
            'type' => Select::class,
            'options' => [
                'label' => $translator->translate('Study'),
                'value_options' => [
                    'bachelor' => [
                        'label' => 'Bachelor',
                        'options' => [
                            'Bachelor Computer Science and Engineering' => 'Computer Science and Engineering',
                            'Bachelor Applied Mathematics' => 'Applied Mathematics',
                        ],
                    ],
                    'master' => [
                        'label' => 'Master',
                        'options' => [
                            'Master Industrial and Applied Mathematics' => 'Industrial and Applied Mathematics',
                            'Master Computer Science and Engineering' => 'Computer Science and Engineering',
                            'Master Data Science in Engineering' => 'Data Science in Engineering',
                            'Master Information Security Technology' => 'Information Security Technology',
                            'Master Embedded Systems' => 'Embedded Systems',
                            'Master Science Education and Communication' => 'Science Education and Communication',
                        ],
                    ],
                    'other' => [
                        'label' => 'Other',
                        'options' => ['Other' => 'Other'],
                    ],
                ],
                'empty_option' => $translator->translate('Select a study'),
            ],
        ]);

        $this->add([
            'name' => 'email',
            'type' => Email::class,
            'options' => [
                'label' => $translator->translate('E-mail Address'),
            ],
        ]);

        $this->add([
            'name' => 'birth',
            'type' => Date::class,
            'options' => [
                'label' => $translator->translate('Birthdate'),
            ],
        ]);

        $student = clone $address;
        $student->setName('address');
        $student->get('type')->setValue(AddressTypes::Student->value);
        $this->add($student);

        $this->add([
            'name' => 'agreed',
            'type' => Checkbox::class,
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => $translator->translate('Subscribe'),
            ],
        ]);
    }

    /**
     * Set the mailing lists.
     *
     * @param MailingListModel[] $lists
     */
    public function setLists(array $lists): void
    {
        $this->lists = $lists;
        foreach ($this->lists as $list) {
            $desc = $list->getNlDescription();

            if ('en' === $this->translator->getLocale()) {
                $desc = $list->getEnDescription();
            }

            $this->add([
                'name' => 'list-' . $list->getName(),
                'type' => 'checkbox',
                'options' => [
                    'label' => '<strong>' . $list->getName() . '</strong> ' . $desc,
                ],
            ]);

            if (!$list->getDefaultSub()) {
                continue;
            }

            $this->get('list-' . $list->getName())->setChecked(true);
        }
    }

    /**
     * Get the mailing lists.
     */
    public function getLists(): array
    {
        return $this->lists;
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'lastName' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 2,
                            'max' => 32,
                        ],
                    ],
                ],
            ],
            'middleName' => [
                'required' => false,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 2,
                            'max' => 32,
                        ],
                    ],
                ],
            ],
            'initials' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 1,
                            'max' => 16,
                        ],
                    ],
                ],
            ],
            'firstName' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => StringLength::class,
                        'options' => [
                            'min' => 1,
                            'max' => 32,
                        ],
                    ],
                ],
            ],
            'birth' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => Callback::class,
                        'options' => [
                            'callback' => function ($value) {
                                return $this->isOldEnough($value);
                            },
                            'messages' => [
                                Callback::INVALID_VALUE => $this->translator->translate(
                                    'Are you sure that you are younger than 10 years?',
                                ),
                            ],
                        ],
                    ],
                ],
            ],
            'email' => [
                'required' => true,
                'filters' => [
                    ['name' => StringToLower::class],
                ],
            ],
            'agreed' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => Identical::class,
                        'options' => [
                            'token' => '1',
                            'messages' => [
                                Identical::NOT_SAME => $this->translator->translate(
                                    'You have to accept the terms!',
                                ),
                            ],
                        ],
                    ],
                ],
            ],
            'tueUsername' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => Regex::class,
                        'options' => [
                            'pattern' => '/^(s\d{6}|\d{8})$/',
                            'messages' => [
                                Regex::NOT_MATCH => $this->translator->translate(
                                    'Your TU/e-username should look like sYYxxxx or YYYYxxxx.',
                                ),
                            ],
                        ],
                    ],
                ],
                'filters' => [
                    ['name' => ToNull::class],
                ],
            ],
        ];
    }

    private function isOldEnough(string $value): bool
    {
        try {
            $longTimeAgo = (new DateTime('now'))->sub(new DateInterval('P10Y'));

            return (new DateTime($value)) < $longTimeAgo;
        } catch (Throwable) {
            return false;
        }
    }
}
