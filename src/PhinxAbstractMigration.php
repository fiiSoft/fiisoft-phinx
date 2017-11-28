<?php

namespace FiiSoft\Phinx;

use LogicException;
use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Db\Table;
use Phinx\Migration\AbstractMigration;
use Phinx\Migration\MigrationInterface;
use Phinx\Util\Util;
use ReflectionClass;

abstract class PhinxAbstractMigration extends AbstractMigration
{
    use PhinxCommonTrait;
    
    /**
     * @param Table|string $tableName
     * @param string $columnName
     * @throws \UnexpectedValueException
     * @return void
     */
    final protected function dropColumn($tableName, $columnName)
    {
        $table = $this->getTable($tableName);
        if ($table->hasColumn($columnName)) {
            $table->removeColumn($columnName);
        }
    }
    
    /**
     * @param string $tableName
     * @param string $columnName
     * @param string $sql
     * @throws \UnexpectedValueException
     * @return void
     */
    final protected function executeIfColumnDoesNotExist($tableName, $columnName, $sql)
    {
        if (!$this->columnExists($tableName, $columnName)) {
            $this->execute($sql);
        }
    }
    
    /**
     * @param string $tableName
     * @param string $sql
     * @throws \UnexpectedValueException
     * @return void
     */
    final protected function executeIfTableDoesNotExist($tableName, $sql)
    {
        if (!$this->hasTable($tableName)) {
            $this->execute($sql);
        }
    }
    
    /**
     * @param string $viewName
     * @param string $sql
     * @throws \UnexpectedValueException
     * @return void
     */
    final protected function executeIfViewDoesNotExist($viewName, $sql)
    {
        if (!$this->hasView($viewName)) {
            $this->execute($sql);
        }
    }
    
    /**
     * @param string $procName
     * @param string $sql
     * @throws \UnexpectedValueException
     * @throws LogicException
     * @return void
     */
    final protected function executeIfProcedureDoesNotExist($procName, $sql)
    {
        if (!$this->procedureExists($procName)) {
            $this->execute($sql);
        }
    }
    
    /**
     * @param string $triggerName
     * @param string $sql
     * @throws \UnexpectedValueException
     * @throws \LogicException
     * @return void
     */
    final protected function executeIfTriggerDoesNotExist($triggerName, $sql)
    {
        if (!$this->triggerExists($triggerName)) {
            $this->execute($sql);
        }
    }
    
    /**
     * @param string $procName
     * @throws \UnexpectedValueException
     * @throws \LogicException
     * @return void
     */
    final protected function dropProcedure($procName)
    {
        if (!$this->procedureExists($procName)) {
            return;
        }
        
        if ($this->isPostgress()) {
            $row = $this->fetchRow(<<<SQL
SELECT string_agg(par, ', ') AS arguments
FROM (
	SELECT format_type(unnest(proargtypes), null) AS par
    FROM pg_catalog.pg_proc
    WHERE proname = '{$this->getNameWithoutSchema($procName)}'
) AS args;
SQL
            );
            
            $this->execute('DROP FUNCTION '.$procName.'('.$row['arguments'].');');
            return;
        }

        $this->caseNotSupportedForCurrentAdapterType();
    }
    
    /**
     * @param string $procName
     * @throws \UnexpectedValueException
     * @throws \LogicException
     * @return bool
     */
    final protected function procedureExists($procName)
    {
        if ($this->isPostgress()) {
            return $this->doesEntryExist('pg_catalog.pg_proc', 'proname', $this->getNameWithoutSchema($procName));
        }
        
        $this->caseNotSupportedForCurrentAdapterType();
    }
    
    /**
     * @param string $triggerName
     * @param string $tableName
     * @throws \UnexpectedValueException
     * @throws \LogicException
     * @return void
     */
    final protected function dropTrigger($triggerName, $tableName)
    {
        if (!$this->triggerExists($triggerName)) {
            return;
        }
    
        if ($this->isPostgress()) {
            $this->execute('DROP TRIGGER '.$this->getNameWithoutSchema($triggerName).' ON '.$tableName.';');
            return;
        }
        
        $this->caseNotSupportedForCurrentAdapterType();
    }
    
    /**
     * @param string $triggerName
     * @throws \UnexpectedValueException
     * @throws \LogicException
     * @return bool
     */
    final protected function triggerExists($triggerName)
    {
        if ($this->isPostgress()) {
            return $this->doesEntryExist('pg_catalog.pg_trigger', 'tgname', $this->getNameWithoutSchema($triggerName));
        }
        
        $this->caseNotSupportedForCurrentAdapterType();
    }
    
    /**
     * @param string $name
     * @return string
     */
    private function getNameWithoutSchema($name)
    {
        if (false !== ($pos = strpos($name, '.'))) {
            return substr($name, $pos + 1);
        }
        
        return $name;
    }
    
    /**
     * @return bool
     */
    private function isPostgress()
    {
        return $this->adapter->getAdapterType() === 'pgsql';
    }
    
    /**
     * @throws \LogicException
     * @return void
     */
    private function caseNotSupportedForCurrentAdapterType()
    {
        throw new LogicException('Case not supported for adapter of type '.$this->adapter->getAdapterType());
    }
    
    /**
     * @param Table|string $tableName
     * @param string $columnName
     * @throws \UnexpectedValueException
     * @return bool
     */
    final protected function columnExists($tableName, $columnName)
    {
        return $this->getTable($tableName)->hasColumn($columnName);
    }
    
    /**
     * @param Table|string $tableName
     * @param array|string $column
     * @param string|null $constraint
     * @throws \UnexpectedValueException
     * @return bool
     */
    final protected function doesTableHaveForeignKey($tableName, $column, $constraint = null)
    {
        return $this->getTable($tableName)->hasForeignKey($column, $constraint);
    }
    
    /**
     * @param Table|string $tableName
     * @param string $indexName
     * @throws \UnexpectedValueException
     * @return void
     */
    final protected function dropIndexByName($tableName, $indexName)
    {
        $this->getTable($tableName)->removeIndexByName($indexName);
    }
    
    /**
     * @param Table|string $tableName
     * @param array|string $column
     * @param string|null $constraint
     * @throws \UnexpectedValueException
     * @return void
     */
    final protected function dropForeignKeyByName($tableName, $column, $constraint = null)
    {
        $table = $this->getTable($tableName);
        if ($table->hasForeignKey($column, $constraint)) {
            $table->dropForeignKey($column, $constraint);
        }
    }
    
    /**
     * @param Table|string $tableName
     * @param array|string $column
     * @param mixed $value
     * @throws \UnexpectedValueException
     * @return void
     */
    final protected function deleteFromTable($tableName, $column, $value = null)
    {
        $table = $this->getTable($tableName);
        $adapter = $table->getAdapter();
        
        $adapter->execute(
            'DELETE FROM '
            .$adapter->quoteTableName($table->getName())
            .' WHERE '.$this->buildWhere($adapter, $column, $value)
        );
    }
    
    /**
     * @param Table|string $tableName
     * @param array|string $whereColumn
     * @param mixed $value
     * @throws \UnexpectedValueException
     * @return array
     */
    final protected function fetchRowFromTable($tableName, $whereColumn, $value = null)
    {
        $table = $this->getTable($tableName);
        $adapter = $table->getAdapter();
        
        return $adapter->fetchRow(
            'SELECT * FROM '.$adapter->quoteTableName($table->getName())
            .' WHERE '.$this->buildWhere($adapter, $whereColumn, $value)
            .' LIMIT 1'
        );
    }
    
    /**
     * @param Table|string $tableName
     * @param array|string $column
     * @param string|null $indexName
     * @throws \UnexpectedValueException
     * @return void
     */
    final protected function createUniqueIndex($tableName, $column, $indexName = null)
    {
        $table = $this->getTable($tableName);
        
        if (!$table->hasIndex($column)) {
            $options['unique'] = true;
            if ($indexName) {
                $options['name'] = $indexName;
            }
            
            $table->addIndex($column, $options);
            $table->save();
        }
    }
    
    /**
     * @param string $tableName
     * @throws \UnexpectedValueException
     * @return void
     */
    final public function dropTable($tableName)
    {
        if ($this->hasTable($tableName)) {
            parent::dropTable($tableName);
        }
    }
    
    /**
     * @param string $viewName
     * @throws \UnexpectedValueException
     * @return void
     */
    final protected function dropView($viewName)
    {
        if ($this->hasView($viewName)) {
            $this->execute('DROP VIEW '.$viewName);
        }
    }
    
    /**
     * @param string $viewName
     * @throws \UnexpectedValueException
     * @return bool
     */
    final protected function hasView($viewName)
    {
        return $this->hasTable($viewName);
    }
    
    /**
     * @param string $tableName
     * @throws \UnexpectedValueException
     * @return bool
     */
    final public function hasTable($tableName)
    {
        list($tableSchema, $tableName) = $this->getSchemaAndTableNameFrom($tableName);
        $this->writelnVerbose('Table name: '.$tableName.' and schema name: '.$tableSchema);
        
        return $this->getAdapterWithSchema($this->adapter, $tableSchema)->hasTable($tableName);
    }
    
    /**
     * @param string $tableName
     * @param array $options
     * @return Table
     */
    final public function table($tableName, $options = array())
    {
        $table = parent::table($tableName, $options);
    
        $tableOptions = $table->getOptions();
        $tableSchema = isset($tableOptions['schema']) ? $tableOptions['schema'] : 'public';
        
        $table->setAdapter($this->getAdapterWithSchema($table->getAdapter(), $tableSchema));
        
        return $table;
    }
    
    /**
     * @param AdapterInterface $adapter
     * @param string $schema
     * @return AdapterInterface
     */
    private function getAdapterWithSchema(AdapterInterface $adapter, $schema)
    {
        $adapterSchema = $adapter->hasOption('schema') ? $adapter->getOption('schema') : 'public';
        if ($adapterSchema === $schema) {
            return $adapter;
        }
    
        $this->writelnVerbose('Adapter has schema: '.$adapterSchema.' but required schema is: '.$schema.' - make copy');
        
        $copy = clone $adapter;
        
        $options = $copy->getOptions();
        $options['schema'] = $schema;
        $copy->setOptions($options);
        
        return $copy;
    }
    
    /**
     * @param string $migrationClass
     * @throws \ReflectionException
     * @return void
     */
    final protected function executeMigrationUp($migrationClass)
    {
        $migration = $this->instantiateMigration($migrationClass);
        $migration->setMigratingUp(true);
        $migration->up();
    }
    
    /**
     * @param string $migrationClass
     * @throws \ReflectionException
     * @return void
     */
    final protected function executeMigrationDown($migrationClass)
    {
        $migration = $this->instantiateMigration($migrationClass);
        $migration->setMigratingUp(false);
        $migration->down();
    }
    
    /**
     * @param string $migrationClass
     * @throws \ReflectionException
     * @return MigrationInterface
     */
    private function instantiateMigration($migrationClass)
    {
        $refl = new ReflectionClass($migrationClass);
        $version = Util::getVersionFromFileName($refl->getFileName());
    
        /* @var $migration MigrationInterface */
        $migration = new $migrationClass($version, $this->input, $this->output);
        $migration->setAdapter($this->adapter);
        
        return $migration;
    }
}