<?php

declare(strict_types=1);

namespace Database\Form\Fieldset;

use Laminas\Form\Element;
use Laminas\Form\Element\Collection;
use Laminas\Form\ElementInterface;
use Laminas\Form\FieldsetInterface;
use ReflectionMethod;
use Traversable;

use function get_parent_class;

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
     * @param array|Traversable $messages
     *
     * @return $this|Element|ElementInterface|FieldsetInterface
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function setMessages(iterable $messages): ElementInterface|FieldsetInterface|Element|self
    {
        // Get the correct parent class
        $collection = get_parent_class($this);
        // this is the one that has got the problem
        $fieldset = get_parent_class($collection);

        // and this is the class that we need
        $element = get_parent_class($fieldset);

        $reflectionMethod = new ReflectionMethod($element, 'setMessages');
        $reflectionMethod->invoke($this, $messages);

        return $this;
    }

    /**
     * Override the setMessage method such that Element::getMessage() is used again
     *
     * @return array Messages set on this element
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    public function getMessages(?string $elementName = null): array
    {
        // Get the correct parent class
        $collection = get_parent_class($this);
        // this is the one that has got the problem
        $fieldset = get_parent_class($collection);

        // and this is the class that we need
        $element = get_parent_class($fieldset);

        $reflectionMethod = new ReflectionMethod($element, 'getMessages');

        return $reflectionMethod->invoke($this, $elementName);
    }
}
