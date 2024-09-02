<?php

declare(strict_types=1);

namespace Database\Form;

use Application\Model\Enums\AddressTypes;
use Database\Form\Fieldset\Address as AddressFieldset;
use Database\Model\MailingList as MailingListModel;
use DateInterval;
use DateTime;
use Laminas\Filter\StringToLower;
use Laminas\Filter\StringTrim;
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
use Laminas\Validator\EmailAddress;
use Laminas\Validator\Hostname;
use Laminas\Validator\Identical;
use Laminas\Validator\Regex;
use Laminas\Validator\StringLength;
use Throwable;

use function date;
use function preg_match;
use function str_ends_with;
use function substr;

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
                        'label' => 'Bachelor Programs',
                        'options' => [
                            'Bachelor Applied Mathematics' => 'Applied Mathematics',
                            'Bachelor Computer Science and Engineering' => 'Computer Science and Engineering',
                            'Bachelor Data Science' => 'Data Science¹',
                        ],
                    ],
                    'premaster' => [
                        'label' => 'Premaster Programs',
                        'options' => [
                            'Pre-master Computer Science and Engineering' => 'Computer Science and Engineering',
                            'Pre-master Data Science and Artificial Intelligence' => 'Data Science and Artificial Intelligence¹',
                            'Pre-master Embedded Systems' => 'Embedded Systems',
                            'Pre-master Industrial and Applied Mathematics' => 'Industrial and Applied Mathematics',
                            'Pre-master Information Security Technology' => 'Information Security Technology',
                            'Schakelprogramma SEC Leraar vho Informatica' => 'Schakelprogramma SEC Leraar vho Informatica',
                            'Schakelprogramma SEC Leraar vho Wiskunde' => 'Schakelprogramma SEC Leraar vho Wiskunde',
                        ],
                    ],
                    'graduate' => [
                        'label' => 'Graduate Programs',
                        'options' => [
                            'Master Artificial Intelligence & Engineering Systems' => 'Artificial Intelligence & Engineering Systems',
                            'Master Computer Science and Engineering' => 'Computer Science and Engineering',
                            'Master Data Science & Artificial Intelligence' => 'Data Science & Artificial Intelligence¹',
                            'Master Data Science in Business and Entrepreneurship' => 'Data Science in Business and Entrepreneurship',
                            'Master Embedded Systems' => 'Embedded Systems',
                            'Master Industrial and Applied Mathematics' => 'Industrial and Applied Mathematics',
                            'Master Information Security Technology' => 'Information Security Technology',
                            'Master Science Education' => 'Science Education',
                        ],
                    ],
                    'phd' => [
                        'label' => 'EngD / PhD Programs',
                        'options' => [
                            'EngD Automotive Systems Design' => 'EngD Automotive Systems Design',
                            'EngD Data Science' => 'EngD Data Science¹',
                            'EngD Mechatronic Systems Design' => 'EngD Mechatronic Systems Design',
                            'EngD Software Technology' => 'EngD Software Technology',
                            'PhD Computer Science' => 'PhD Computer Science',
                            'PhD Data Science' => 'PhD Data Science¹',
                            'PhD Mathematics' => 'PhD Mathematics',
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
            'name' => 'agreedStripe',
            'type' => Checkbox::class,
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => $translator->translate('Go to checkout'),
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
                'filters' => [
                    [
                        'name' => StringTrim::class,
                    ],
                ],
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
                'filters' => [
                    [
                        'name' => StringTrim::class,
                    ],
                ],
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
                'filters' => [
                    [
                        'name' => StringTrim::class,
                    ],
                ],
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
                'filters' => [
                    [
                        'name' => StringTrim::class,
                    ],
                ],
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
                'validators' => [
                    [
                        'name' => Callback::class,
                        'options' => [
                            'callback' => static function ($value) {
                                return !str_ends_with($value, '.tue.nl');
                            },
                            'messages' => [
                                Callback::INVALID_VALUE => $this->translator->translate(
                                    // phpcs:ignore -- user-visible strings should not be split
                                    'You cannot use your TU/e (student) e-mail address because if you leave or stop studying, we can no longer reach you about important announcements.',
                                ),
                            ],
                        ],
                    ],
                    [
                        'name' => EmailAddress::class,
                        'options' => [
                            'allow' => Hostname::ALLOW_DNS,
                            'useMxCheck' => true,
                            'messages' => [
                                EmailAddress::INVALID_MX_RECORD => $this->translator->translate(
                                    // phpcs:ignore -- user-visible strings should not be split
                                    'Please check your e-mail address, \'%hostname%\' does not appear to be able to receive e-mails. If you are certain that your e-mail address is correct, please contact the board.'
                                ),
                            ],
                        ],
                    ],
                ],
                'filters' => [
                    [
                        'name' => StringToLower::class,
                    ],
                    [
                        'name' => StringTrim::class,
                    ],
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
                                    'You cannot become a member of the association without agreeing to the terms.',
                                ),
                            ],
                        ],
                    ],
                ],
            ],
            'agreedStripe' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => Identical::class,
                        'options' => [
                            'token' => '1',
                            'messages' => [
                                Identical::NOT_SAME => $this->translator->translate(
                                    'To pay the membership fee you must accept Stripe\'s privacy policy.',
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
                    [
                        'name' => Callback::class,
                        'options' => [
                            'callback' => function ($value) {
                                return $this->isNewTueUsernameValid($value);
                            },
                            'messages' => [
                                Callback::INVALID_VALUE => $this->translator->translate(
                                    // phpcs:ignore -- user-visible strings should not be split
                                    'Your TU/e-username appears to be incorrect. Ensure that it starts with a valid year and looks like: YYYYxxxx. If you believe your TU/e-username is correct, please contact the secretary.',
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

    private function isNewTueUsernameValid(string $value): bool
    {
        try {
            // Only check for YYYYABCD TU/e usernames.
            if (preg_match('/^s\d{6}$/', $value)) {
                return true;
            }

            $year = substr($value, 0, 4);
            $currentYear = date('Y');

            // Check if the year is within the valid range, the assumption being that you can never have a number
            // starting with a year that is higher than the current year.
            return $year >= 2000 && $year <= $currentYear;
        } catch (Throwable) {
            return false;
        }
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
