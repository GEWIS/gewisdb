<?php

namespace Database\Form;

use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\I18n\Translator\TranslatorInterface as Translator;

use Database\Model\Address;

class Member extends Form implements InputFilterProviderInterface
{

    /**
     * Lists
     */
    protected $lists;

    public function __construct(Fieldset\Address $address, Translator $translator)
    {
        parent::__construct();

        $this->add(array(
            'name' => 'lastName',
            'type' => 'text',
            'options' => array(
                'label' => $translator->translate('Achternaam')
            )
        ));

        $this->add(array(
            'name' => 'middleName',
            'type' => 'text',
            'options' => array(
                'label' => $translator->translate('Tussenvoegsels')
            )
        ));

        $this->add(array(
            'name' => 'initials',
            'type' => 'text',
            'options' => array(
                'label' => $translator->translate('Voorletter(s)')
            )
        ));

        $this->add(array(
            'name' => 'firstName',
            'type' => 'text',
            'options' => array(
                'label' => $translator->translate('Voornaam')
            )
        ));

        $this->add(array(
            'name' => 'gender',
            'type' => 'radio',
            'options' => array(
                'label' => $translator->translate('Geslacht'),
                'value_options' => array(
                    'm' => 'Man',
                    'f' => 'Vrouw'
                )
            )
        ));

        $this->add(array(
            'name' => 'email',
            'type' => 'email',
            'options' => array(
                'label' => $translator->translate('Email-adres')
            )
        ));

        $this->add(array(
            'name' => 'birth',
            'type' => 'date',
            'options' => array(
                'label' => $translator->translate('Geboortedatum')
            )
        ));


        $home = clone $address;
        $home->setName('homeAddress');
        $home->get('type')->setValue(Address::TYPE_HOME);
        $this->add($home);

        $student = clone $address;
        $student->setName('studentAddress');
        $student->get('type')->setValue(Address::TYPE_STUDENT);
        $this->add($student);

        $this->add(array(
            'name' => 'agreed',
            'type' => 'checkbox'
        ));

        $this->add(array(
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => array(
                'value' => $translator->translate('Schrijf in')
            )
        ));
    }

    /**
     * Set the mailing lists.
     *
     * @param array $lists Array of mailing lists
     */
    public function setLists($lists)
    {
        $this->lists = $lists;
        foreach ($this->lists as $list) {
            $this->add(array(
                'name' => 'list-' . $list->getName(),
                'type' => 'checkbox',
                'options' => array(
                    'label' => '<strong>' . $list->getName() . '</strong> ' . $list->getDescription()
                )
            ));
            if ($list->getDefaultSub()) {
                $this->get('list-' . $list->getName())->setChecked(true);
            }
        }
    }

    /**
     * Get the mailing lists.
     *
     * @return array of mailing lists
     */
    public function getLists()
    {
        return $this->lists;
    }

    /**
     * Specification of input filter.
     */
    public function getInputFilterSpecification()
    {
        return array(
            'lastName' => array(
                'required' => true,
                'validators' => array(
                    array(
                        'name' => 'string_length',
                        'options' => array(
                            'min' => 2,
                            'max' => 32
                        )
                    )
                )
            ),
            'middleName' => array(
                'required' => false,
                'validators' => array(
                    array(
                        'name' => 'string_length',
                        'options' => array(
                            'min' => 2,
                            'max' => 32
                        )
                    )
                )
            ),
            'initials' => array(
                'required' => true,
                'validators' => array(
                    array(
                        'name' => 'string_length',
                        'options' => array(
                            'min' => 1,
                            'max' => 16
                        )
                    )
                )
            ),
            'firstName' => array(
                'required' => true,
                'validators' => array(
                    array(
                        'name' => 'string_length',
                        'options' => array(
                            'min' => 2,
                            'max' => 32
                        )
                    )
                )
            ),
            'agreed' => array(
                'required' => true,
                'validators' => array(
                    array(
                        'name' => 'identical',
                        'options' => array(
                            'token' => '1',
                            'messages' => array(
                                'notSame' => 'Je moet de voorwaarden accepteren!'
                            )
                        )
                    )
                )
            )
        );
    }
}
