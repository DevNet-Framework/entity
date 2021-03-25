<?php declare(strict_types = 1);
/**
 * @author      Mohammed Moussaoui
 * @copyright   Copyright (c) Mohammed Moussaoui. All rights reserved.
 * @license     MIT License. For full license information see LICENSE file in the project root.
 * @link        https://github.com/artister
 */

namespace Artister\Entity\Internal;

use Artister\System\Linq;
use Artister\System\Linq\IQueryable;
use Artister\System\Collections\Enumerator;
use Artister\Entity\IEntity;
use Artister\Entity\Query\EntityQuery;
use Artister\Entity\Metadata\EntityType;
use Artister\Entity\Metadata\EntityNavigation;
use Artister\Entity\Storage\EntityDatabase;
use Artister\Entity\Tracking\EntityStateManager;

class EntityFinder
{
    private EntityDatabase $Database;
    private EntityStateManager $EntityStateManager;

    public function __construct(EntityDatabase $database)
    {
        $this->Database             = $database;
        $this->EntityStateManager   = $database->EntityStateManager;
    }

    public function find(EntityType $entityType, $id) : ?IEntity
    {
        $entry = $this->EntityStateManager->getEntry($entityType->getName(), $id);
        if ($entry)
        {
            return $entry->Entity;
        }

        $query  = new EntityQuery($entityType, $this->Database->QueryProvider);
        $key    = $entityType->getPrimaryKey();

        $entity = $query->where(fn($entity) => $entity->$key == $id)->first();

        if ($entity)
        {
            $this->load($entity);
        }

        return $entity;
    }

    public function load(IEntity $entity)
    {
        $this->Database->attach($entity);

        $entityType = $this->Database->Model->getEntityType(get_class($entity));
        $key = $entityType->PropertyKey;

        foreach ($entityType->Navigations as $navigation)
        {
            $navigation->PropertyInfo->setAccessible(true);
            if ($navigation->NavigationType == 2)
            {
                $navigation->PropertyInfo->setValue($entity, new EntityCollection($navigation, $this->Database, $entity->$key));
            }
            else if ($navigation->NavigationType == 1)
            {
                $foreignKey = $navigation->Metadata->getForeignKey($navigation->MetadataReference->getName());
                if ($foreignKey)
                {
                    $ParentEntity = $this->find($navigation->MetadataReference, $entity->$foreignKey);
                    $navigation->PropertyInfo->setValue($entity, $ParentEntity);
                }
                else
                {
                    $childEntity = $this->query($navigation, $entity->$key)->current();
                    if ($childEntity)
                    {
                        $navigation->PropertyInfo->setValue($entity, $childEntity);
                    }
                }
            }
        }
    }

    public function query(EntityNavigation $navigation, $keyValue) : Enumerator
    {
        $query      = new EntityQuery($navigation->MetadataReference, $this->Database->QueryProvider);
        $foreignKey = $navigation->getForeignKey();

        return $query->where(fn($entity) => $entity->$foreignKey ==  $keyValue)->getIterator();
    }
}
