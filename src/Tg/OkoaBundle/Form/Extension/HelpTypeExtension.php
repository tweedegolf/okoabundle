<?php

namespace Tg\OkoaBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use ReflectionProperty;

/**
 * Provides help text
 */
class HelpTypeExtension extends AbstractTypeExtension
{
    private $reader;

    /**
     * Set the annotation reader to be used
     */
    public function setAnnotationReader($reader)
    {
        $this->reader = $reader;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $help = $options['help'];
        if ($help === null && $builder->hasParent()) {
            $parentClass = $builder->getParent()->getOption('data_class');
            $fieldName = $builder->getName();
            if ($parentClass !== null) {
                $reflector = new ReflectionProperty($parentClass, $fieldName);
                $annot = $this->reader->getPropertyAnnotation(
                    $reflector,
                    'Tg\OkoaBundle\Form\Annotation\Help'
                );

                if ($annot !== null) {
                    $help = $annot->value;
                }
            }
        }
        $builder->setAttribute('help', $help);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['help'] = $form->getConfig()->getAttribute('help');
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'help' => null
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'field';
    }
}
