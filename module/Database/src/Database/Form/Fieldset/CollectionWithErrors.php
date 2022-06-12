<?php

/**
 * Created by PhpStorm.
 * User: stefan
 * Date: 11-4-15
 * Time: 15:55
 */

namespace Database\Form\Fieldset;

use Zend\Form\Element\Collection;

/**
 * The normal collection class can not set errors,
 * which means that errors set during validation over the
 * whole collection are lost (e.g. if the collection is empty)
 * This class is a collection that handles errors like a normal
 * component.
 */
class CollectionWithErrors extends Collection
{
    /**
     * Override the setMessage method such that Element::setMessage() is used again
     *
     * @param array|\Traversable $messages
     * @return $this|\Zend\Form\Element|\Zend\Form\ElementInterface|\Zend\Form\FieldsetInterface
     */
    public function setMessages($messages)
    {
        // Get the correct parent class
        $collection = get_parent_class($this);
        // this is the one that has got the problem
        $fieldset = get_parent_class($collection);

        // and this is the class that we need
        $element = get_parent_class($fieldset);

        $reflectionMethod = new \ReflectionMethod($element, 'setMessages');
        $reflectionMethod->invoke($this, $messages);
        return $this;
    }

    /**
     * Override the setMessage method such that Element::getMessage() is used again
     *
     * @return array Messages set on this ellement
     */
    public function getMessages()
    {
        // Get the correct parent class
        $collection = get_parent_class($this);
        // this is the one that has got the problem
        $fieldset = get_parent_class($collection);

        // and this is the class that we need
        $element = get_parent_class($fieldset);

        $reflectionMethod = new \ReflectionMethod($element, 'getMessages');
        return $reflectionMethod->invoke($this);
    }
}
