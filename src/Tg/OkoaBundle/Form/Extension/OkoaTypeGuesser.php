<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tg\OkoaBundle\Form\Extension;

use Closure;
use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\Guess\Guess;
use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\Form\Guess\ValueGuess;
use Symfony\Component\Validator\MetadataFactoryInterface;
use Symfony\Component\Validator\Constraint;
use Tg\OkoaBundle\Util\ArrayUtil;

/**
 * Guess types for those fields that symfony doesn't do it already.
 */
class OkoaTypeGuesser implements FormTypeGuesserInterface
{
    private $metadataFactory;

    public function __construct(MetadataFactoryInterface $metadataFactory)
    {
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function guessType($class, $property)
    {
        $guesser = $this;
        return $this->guess($class, $property, function ($annot) use ($guesser, $class, $property) {
            return $guesser->guessTypeForAnnotation($annot, $class, $property);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function guessRequired($class, $property)
    {
        $guesser = $this;
        return $this->guess($class, $property, function ($annot) use ($guesser, $class, $property) {
            return $guesser->guessRequiredForAnnotation($annot, $class, $property);
        }, false);
    }

    /**
     * {@inheritDoc}
     */
    public function guessMaxLength($class, $property)
    {
        $guesser = $this;
        return $this->guess($class, $property, function ($annot) use ($guesser, $class, $property) {
            return $guesser->guessMaxLengthForAnnotation($annot, $class, $property);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function guessMinLength($class, $property)
    {
        trigger_error('guessMinLength() is deprecated since version 2.1 and will be removed in 2.3.', E_USER_DEPRECATED);
    }

    /**
     * {@inheritDoc}
     */
    public function guessPattern($class, $property)
    {
        $guesser = $this;
        return $this->guess($class, $property, function ($annot) use ($guesser, $class, $property) {
            return $guesser->guessPatternForAnnotation($annot, $class, $property);
        });
    }

    /**
     * Guesses a field class name for a given constraint
     * @param object $annot The annotation to guess for
     * @return TypeGuess The guessed field class and options
     */
    public function guessTypeForAnnotation($annot, $class, $property)
    {
        switch (get_class($annot)) {
            case 'Symfony\Component\Validator\Constraints\Choice':
                $options = null;
                if ($annot->choices && is_array($annot->choices)) {
                    $options = $annot->choices;
                } else if (is_string($annot->callback)) {
                    $callback = $annot->callback;
                    $options = call_user_func([$class, $callback]);
                } else if (is_array($annot->callback)) {
                    $options = call_user_func($annot->callback);
                }
                if (is_array($options)) {
                    if (ArrayUtil::isAssociative($options)) {
                        $options = array_combine(array_values($options), array_keys($options));
                    } else {
                        $values = array_values($options);
                        $options = array_combine($values, $values);
                    }
                    return new TypeGuess('choice', ['choices' =>  $options], Guess::HIGH_CONFIDENCE);
                }

        }
        return null;
    }

    /**
     * Guesses whether a field is required based on the given annotation
     * @param object $annot The annotation to guess for
     * @return Guess The guess whether the field is required
     */
    public function guessRequiredForAnnotation($annot, $class, $property)
    {
        return null;
    }

    /**
     * Guesses a field's maximum length based on the given annotation
     * @param object $annot The annotation to guess for
     * @return Guess The guess for the maximum length
     */
    public function guessMaxLengthForAnnotation($annot, $class, $property)
    {
        return null;
    }

    /**
     * Guesses a field's pattern based on the given annotation
     * @param object $annot The annotation to guess for
     * @return Guess The guess for the pattern
     */
    public function guessPatternForAnnotation($annot, $class, $property)
    {
        return null;
    }

    /**
     * Iterates over the constraints of a property, executes a constraints on
     * them and returns the best guess
     * @param string   $class        The class to read the constraints from
     * @param string   $property     The property for which to find constraints
     * @param Closure  $closure      The closure that returns a guess
     *                               for a given constraint
     * @param mixed    $defaultValue The default value assumed if no other value
     *                               can be guessed.
     * @return Guess The guessed value with the highest confidence
     */
    protected function guess($class, $property, Closure $closure, $defaultValue = null)
    {
        $guesses = array();
        $classMetadata = $this->metadataFactory->getMetadataFor($class);

        if ($classMetadata->hasMemberMetadatas($property)) {
            $memberMetadatas = $classMetadata->getMemberMetadatas($property);
            foreach ($memberMetadatas as $memberMetadata) {
                $constraints = $memberMetadata->getConstraints();

                foreach ($constraints as $constraint) {
                    if ($guess = $closure($constraint)) {
                        $guesses[] = $guess;
                    }
                }
            }

            if (null !== $defaultValue) {
                $guesses[] = new ValueGuess($defaultValue, Guess::LOW_CONFIDENCE);
            }
        }

        return Guess::getBestGuess($guesses);
    }
}
