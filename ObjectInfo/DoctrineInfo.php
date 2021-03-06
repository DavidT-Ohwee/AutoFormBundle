<?php

/*
 * This file is part of A2lix projects.
 *
 * (c) David ALLIX
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace A2lix\AutoFormBundle\ObjectInfo;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

class DoctrineInfo implements ObjectInfoInterface
{
    /** @var ClassMetadataFactory */
    private $classMetadataFactory;

    /**
     * @param ClassMetadataFactory $classMetadataFactory
     */
    public function __construct(ClassMetadataFactory $classMetadataFactory)
    {
        $this->classMetadataFactory = $classMetadataFactory;
    }

    /**
     * @param string $class
     *
     * @return array
     */
    public function getFieldsConfig($class)
    {
        $fieldsConfig = [];

        $metadata = $this->classMetadataFactory->getMetadataFor($class);

        if ($fields = $metadata->getFieldNames()) {
            $fieldsConfig = array_fill_keys($fields, []);
        }

        if ($assocNames = $metadata->getAssociationNames()) {
            $fieldsConfig += $this->getAssocsConfig($metadata, $assocNames);
        }

        return $fieldsConfig;
    }

    /**
     * @param ClassMetadata $metadata
     * @param array         $assocNames
     *
     * @return array
     */
    private function getAssocsConfig(ClassMetadata $metadata, $assocNames)
    {
        $assocsConfigs = [];

        foreach ($assocNames as $assocName) {
            if (!$metadata->isAssociationInverseSide($assocName)) {
                continue;
            }

            $class = $metadata->getAssociationTargetClass($assocName);

            if ($metadata->isSingleValuedAssociation($assocName)) {
                $nullable = ($metadata instanceof ClassMetadataInfo) && isset($metadata->discriminatorColumn['nullable']) && $metadata->discriminatorColumn['nullable'];

                $assocsConfigs[$assocName] = [
                    'field_type' => 'A2lix\AutoFormBundle\Form\Type\AutoFormType',
                    'data_class' => $class,
                    'required' => !$nullable,
                ];

                continue;
            }

            $assocsConfigs[$assocName] = [
                'field_type' => 'Symfony\Component\Form\Extension\Core\Type\CollectionType',
                'entry_type' => 'A2lix\AutoFormBundle\Form\Type\AutoFormType',
                'entry_options' => [
                    'data_class' => $class,
                ],
                'allow_add' => true,
                'by_reference' => false,
            ];
        }

        return $assocsConfigs;
    }

    /**
     * @param string $class
     * @param string $fieldName
     *
     * @throws \Exception
     *
     * @return string
     */
    public function getAssociationTargetClass($class, $fieldName)
    {
        $metadata = $this->classMetadataFactory->getMetadataFor($class);

        if (!$metadata->hasAssociation($fieldName)) {
            throw new \Exception(sprintf('Unable to find the association target class of "%s" in %s.', $fieldName, $class));
        }

        return $metadata->getAssociationTargetClass($fieldName);
    }
}
