<?php

namespace FlexModel\FlexModelBundle\Form;

use FlexModel\FlexModel;
use ReflectionClass;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * FlexModelFormType.
 *
 * @author Niels Nijens <niels@connectholland.nl>
 */
class FlexModelFormType extends AbstractType
{
    /**
     * The FlexModel instance.
     *
     * @var FlexModel
     */
    private $flexModel;

    /**
     * Constructs a new FlexModelFormType instance.
     *
     * @param FlexModel $flexModel
     */
    public function __construct(FlexModel $flexModel)
    {
        $this->flexModel = $flexModel;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (isset($options['data_class']) && isset($options['form_name'])) {
            $reflectionClass = new ReflectionClass($options['data_class']);
            $objectName = $reflectionClass->getShortName();

            $formConfiguration = $this->flexModel->getFormConfiguration($objectName, $options['form_name']);
            if (is_array($formConfiguration)) {
                foreach ($formConfiguration['fields'] as $formFieldConfiguration) {
                    $fieldConfiguration = $this->flexModel->getField($objectName, $formFieldConfiguration['name']);

                    $fieldType = $this->getFieldType($formFieldConfiguration, $fieldConfiguration);
                    $fieldOptions = $this->getFieldOptions($formFieldConfiguration, $fieldConfiguration);

                    $builder->add($fieldConfiguration['name'], $fieldType, $fieldOptions);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('form_name', null);
    }

    /**
     * Returns the field type for a field.
     *
     * @param array $formFieldConfiguration
     * @param array $fieldConfiguration
     *
     * @return array
     */
    private function getFieldType(array $formFieldConfiguration, array $fieldConfiguration)
    {
        if (isset($formFieldConfiguration['fieldtype'])) {
            return $formFieldConfiguration['fieldtype'];
        }

        $fieldType = null;
        switch ($fieldConfiguration['datatype']) {
            case 'BOOLEAN':
                $fieldType = null;
                break;
            case 'DATE':
                $fieldType = DateType::class;
                break;
            case 'DATEINTERVAL':
                $fieldType = TextType::class;
                break;
            case 'DATETIME':
                $fieldType = DateTimeType::class;
                break;
            case 'DECIMAL':
                $fieldType = NumberType::class;
                break;
            case 'FILE':
                $fieldType = FileType::class;
                break;
            case 'FLOAT':
                $fieldType = NumberType::class;
                break;
            case 'INTEGER':
                $fieldType = IntegerType::class;
                break;
            case 'SET':
                $fieldType = ChoiceType::class;
                break;
            case 'TEXT':
            case 'HTML':
            case 'JSON':
                $fieldType = TextareaType::class;
                break;
            case 'VARCHAR':
                $fieldType = TextType::class;
                if (isset($fieldConfiguration['options'])) {
                    $fieldType = ChoiceType::class;
                }
                break;
        }

        return $fieldType;
    }

    /**
     * Returns the field options for a field.
     *
     * @param array $formFieldConfiguration
     * @param array $fieldConfiguration
     *
     * @return array
     */
    private function getFieldOptions(array $formFieldConfiguration, array $fieldConfiguration)
    {
        $options = array(
            'label' => $fieldConfiguration['label'],
            'required' => false,
            'constraints' => array(),
        );
        if (isset($fieldConfiguration['required'])) {
            $options['required'] = $fieldConfiguration['required'];
        }
        $this->addFieldOptionsByDatatype($options, $fieldConfiguration);
        $this->addFieldChoiceOptions($options, $fieldConfiguration);
        $this->addFieldConstraintOptions($options, $formFieldConfiguration);

        return $options;
    }

    /**
     * Adds field options based on the datatype of a field.
     */
    private function addFieldOptionsByDatatype(array & $options, array $fieldConfiguration)
    {
        switch ($fieldConfiguration['datatype']) {
            case 'SET':
                $options['multiple'] = true;
                break;
        }
    }

    /**
     * Adds the choices option to the field options.
     *
     * @param array $options
     * @param array $fieldConfiguration
     */
    private function addFieldChoiceOptions(array & $options, array $fieldConfiguration)
    {
        if (isset($fieldConfiguration['options'])) {
            $options['choices'] = array();
            foreach ($fieldConfiguration['options'] as $option) {
                $options['choices'][$option['label']] = $option['value'];
            }
        }
    }

    /**
     * Adds the constraints option to the field options.
     *
     * @param array $options
     * @param array $formFieldConfiguration
     */
    private function addFieldConstraintOptions(array & $options, array $formFieldConfiguration)
    {
        if ($options['required'] === true) {
            $options['constraints'][] = new NotBlank();
        }

        if (isset($formFieldConfiguration['validators'])) {
            $options['constraints'] = array();
            foreach ($formFieldConfiguration['validators'] as $validatorClass => $validatorOptions) {
                $options['constraints'][] = new $validatorClass($validatorOptions);
            }
        }
    }
}