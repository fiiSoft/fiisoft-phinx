<?php

namespace FiiSoft\Phinx;

use LogicException;
use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Db\Table;
use Phinx\Migration\AbstractMigration;
use Phinx\Migration\MigrationInterface;
use Phinx\Util\Util;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Console\Output\OutputInterface;
use UnexpectedValueException;

abstract class PhinxAbstractMigration extends AbstractMigration
{
    /**
     * @param Table|string $tableName
     * @param string $columnName
     * @throws UnexpectedValueException
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
     * @throws UnexpectedValueException
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
     * @return void
     */
    final protected function executeIfViewDoesNotExist($viewName, $sql)
    {
        if (!$this->hasTable($viewName)) {
            $this->execute($sql);
        }
    }
    
    /**
     * @param string $procName
     * @param string $sql
     * @throws UnexpectedValueException
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
     * @throws UnexpectedValueException
     * @throws LogicException
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
     * @throws UnexpectedValueException
     * @throws LogicException
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
     * @throws UnexpectedValueException
     * @throws LogicException
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
     * @throws UnexpectedValueException
     * @throws LogicException
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
     * @throws UnexpectedValueException
     * @throws LogicException
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
     * @throws LogicException
     * @return void
     */
    private function caseNotSupportedForCurrentAdapterType()
    {
        throw new LogicException('Case not supported for adapter of type '.$this->adapter->getAdapterType());
    }
    
    /**
     * @param Table|string $tableName
     * @param string $columnName
     * @throws UnexpectedValueException
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
     * @throws UnexpectedValueException
     * @return bool
     */
    final protected function doesTableHaveForeignKey($tableName, $column, $constraint = null)
    {
        return $this->getTable($tableName)->hasForeignKey($column, $constraint);
    }
    
    /**
     * @param Table|string $tableName
     * @param string $indexName
     * @throws UnexpectedValueException
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
     * @throws UnexpectedValueException
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
     * @param mixed|null $value
     * @throws UnexpectedValueException
     * @return bool
     */
    final protected function doesEntryExist($tableName, $column, $value = null)
    {
        $table = $this->getTable($tableName);
        $adapter = $table->getAdapter();
        
        $row = $adapter->fetchRow(
            'SELECT COUNT(1) AS cnt FROM '
            .$adapter->quoteTableName($table->getName())
            .' WHERE '.$this->buildWhere($adapter, $column, $value)
        );
        
        return $row['cnt'] > 0;
    }
    
    /**
     * @param Table|string $tableName
     * @param array|string $column
     * @param mixed $value
     * @throws UnexpectedValueException
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
     * @throws UnexpectedValueException
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
     * @throws UnexpectedValueException
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
     * @return void
     */
    final protected function dropView($viewName)
    {
        if ($this->hasTable($viewName)) {
            $this->execute('DROP VIEW '.$viewName);
        }
    }
    
    /**
     * @param AdapterInterface $adapter
     * @param array|string $column
     * @param mixed $value
     * @return string
     */
    final protected function buildWhere(AdapterInterface $adapter, $column, $value)
    {
        if (is_array($column)) {
            $pieces = [];
    
            /** @noinspection ForeachSourceInspection */
            foreach ($column as $col => $val) {
                $pieces[] = $adapter->quoteColumnName($col).' = '.$this->quote($val);
            }
            
            return implode(' AND ', $pieces);
        }
    
        return $adapter->quoteColumnName($column).' = '.$this->quote($value);
    }
    
    /**
     * @param mixed $value
     * @return mixed
     */
    final protected function quote($value)
    {
        if (is_string($value)) {
            return "'".str_replace("'", "\'", $value)."'";
        }
    
        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }
        
        return $value;
    }
    
    /**
     * @param Table|string $tableName
     * @param array $options
     * @throws UnexpectedValueException
     * @return Table
     */
    final protected function getTable($tableName, array $options = [])
    {
        if ($tableName instanceof Table) {
            return $tableName;
        }
        
        if (false !== ($pos = strpos($tableName, '.'))) {
            $fullName = $tableName;

            $options['schema'] = substr($fullName, 0, $pos);
            $tableName = substr($fullName, $pos + 1);

            if ($tableName === false) {
                throw new UnexpectedValueException('Error while retrieving schema and table name from '.$fullName);
            }
        }

        $this->writelnVerbose(
            'Table name: '.$tableName.' and schema: '.(isset($options['schema']) ? $options['schema'] : '')
        );
        
        return $this->table($tableName, $options);
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
        $tableAdapter = $table->getAdapter();
        
        $tableSchema = isset($tableOptions['schema']) ? $tableOptions['schema'] : 'public';
        $adapterSchema = $tableAdapter->hasOption('schema') ? $tableAdapter->getOption('schema') : 'public';
        
        if ($tableSchema !== $adapterSchema) {
            $this->writelnVerbose('Table schema: '.$tableSchema.', adapter schema: '.$adapterSchema);
            $adapter = clone $tableAdapter;
            $options = $adapter->getOptions();
            $options['schema'] = $tableSchema;
            $adapter->setOptions($options);
            $table->setAdapter($adapter);
        }
        
        return $table;
    }
    
    /**
     * @param string $message
     * @return void
     */
    private function writelnVerbose($message)
    {
        $this->output->writeln($message, OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE);
    }
    
    /**
     * @param string $migrationClass
     * @throws ReflectionException
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
     * @throws ReflectionException
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
     * @throws ReflectionException
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