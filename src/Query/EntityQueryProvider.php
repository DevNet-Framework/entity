<?php declare(strict_types = 1);
/**
 * @author      Mohammed Moussaoui
 * @copyright   Copyright (c) Mohammed Moussaoui. All rights reserved.
 * @license     MIT License. For full license information see LICENSE file in the project root.
 * @link        https://github.com/artister
 */

namespace Artister\Data\Entity\Query;

use Artister\Data\Entity\Storage\EntityMapper;
use Artister\System\Database\DbConnection;
use Artister\System\Linq\IQueryable;
use Artister\System\Linq\IQueryProvider;
use Artister\System\Compiler\Expressions\Expression;
use Artister\System\Collections\Enumerator;

class EntityQueryProvider implements IQueryProvider
{
    private DbConnection $Connection;
    private EntityMapper $Mapper;

    public function __construct(EntityMapper $mapper)
    {
        $this->Connection   = $mapper->Connection;
        $this->Mapper       = $mapper;
    }

    public function CreateQuery(object $entityType, Expression $expression = null) : IQueryable
    {
        return new EntityQuery($entityType, $this, $expression);
    }

    public function execute(object $entityType, Expression $expression)
    {
        $translator = new EntityQueryTranslator();
        $translator->visit($expression);
        $slq = $translator->Out;
        
        $this->Connection->open();
        $command = $this->Connection->createCommand($slq);
        if ($translator->OuterVariables)
        {
            $command->addParameters($translator->OuterVariables);
        }

        $dbReader = $command->executeReader($entityType->getName());

        if (!$dbReader)
        {
            return new Enumerator();
        }

        $entities = [];
        foreach ($dbReader as $entity)
        {
            $entities[] = $entity;
            $entry = $this->Mapper->EntityStateManager->getEntry($entity);
            if ($entry)
            {
                $this->Mapper->EntityStateManager->addEntry($entity);
            }
        }

        return new Enumerator($entities);
    }

    public function GetQueryText(Expression $expression) : string
    {
        $translator = new EntityQueryTranslator();
        $translator->visit($expression);
        return $translator->Out;
    }
}