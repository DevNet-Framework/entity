<?php declare(strict_types = 1);
/**
 * @author      Mohammed Moussaoui
 * @copyright   Copyright (c) Mohammed Moussaoui. All rights reserved.
 * @license     MIT License. For full license information see LICENSE file in the project root.
 * @link        https://github.com/DevNet-Framework
 */

namespace DevNet\Entity\Providers\Sqlite;

use DevNet\Entity\Storage\IEntityDataProvider;
use DevNet\Entity\Storage\IEntityPersister;
use DevNet\System\Database\DbConnection;
use DevNet\System\Compiler\ExpressionVisitor;
use DevNet\System\Exceptions\PropertyException;

class SqliteDataProvider implements IEntityDataProvider
{   
    private string $Name = 'Sqlite';
    private DbConnection $Connection;
    private IEntityPersister $Persister;
    private ExpressionVisitor $Visitor;

    public function __construct(DbConnection $connection)
    {
        $this->Connection = $connection;
        $this->Persister  = new SqliteEntityPersister($connection);
        $this->Visitor    = new SqliteQueryTranslator();
    }

    public function __get(string $name)
    {
        if (!in_array($name, ['Name', 'Connection', 'Persister', 'Visitor']))
        {
            throw PropertyException::undefinedPropery(self::class, $name);
        }

        return $this->$name;
    }
}
