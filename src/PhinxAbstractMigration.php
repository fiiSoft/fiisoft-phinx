<?php

namespace FiiSoft\Phinx;

use LogicException;
use Phinx\Db\Table;
use Phinx\Db\Table\ForeignKey;
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
     * @param string $constraint
     * @param string|null $additional can be RESTRICT or CASCADE
     * @throws \UnexpectedValueException
     * @return void
     */
    final protected function dropConstraintByName($tableName, $constraint, $additional = null)
    {
        $table = $this->getTable($tableName);
        $adapter = $table->getAdapter();
        
        $sql = 'ALTER TABLE '.$adapter->quoteTableName($table->getName()).' DROP CONSTRAINT '.$constraint;
    
        if ($additional !== null) {
            $additional = strtoupper($additional);
            if ($additional === 'RESTRICT' || $additional === 'CASCADE') {
                $sql .= ' '.$additional;
            }
        }
        
        $adapter->execute($sql);
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
        list($tableName, $options) = $this->prepareTableNameAndOptions($tableName, $options);
        $table = parent::table($tableName, $options);
        
        $schema = isset($options['schema']) ? $options['schema'] : 'public';
        $table->setAdapter($this->getAdapterWithSchema($table->getAdapter(), $schema));
        
        return $table;
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
    
    /**
     * @param Table $table
     * @param string $tableName
     * @param array $fks format of each: [col, table, tablecol, ondelete?, onupdate?, fkname?] (? are optionall)
     * @return void
     */
    final protected function addForeignKeysToTable(Table $table, $tableName, array $fks)
    {
        foreach ($fks as $fk) {
            $table->addForeignKey($fk[0], $fk[1], $fk[2], [
                'delete' => isset($fk[3]) ? $fk[3] : ForeignKey::CASCADE,
                'update' => isset($fk[4]) ? $fk[4] : ForeignKey::RESTRICT,
                'constraint' => $this->createForeginKeyName($tableName, $fk),
            ]);
        }
    }
    
    /**
     * @param string $tableName
     * @param array $fks
     * @throws \UnexpectedValueException
     * @return void
     */
    final protected function dropForeignKeys($tableName, array $fks)
    {
        foreach ($fks as $fk) {
            $this->dropForeignKeyByName($tableName, $fk[0], $this->createForeginKeyName($tableName, $fk));
        }
    }
    
    /**
     * @param Table $table
     * @param string $tableName
     * @param array $indexes format of each: [column, unique?, name?] (? are optionall)
     * @return void
     */
    final protected function addIndexesToTable(Table $table, $tableName, array $indexes)
    {
        foreach ($indexes as $index) {
            $table->addIndex($index[0], [
                'name' => $this->createIndexName($tableName, $index),
                'unique' => isset($index[1]) ? (bool) $index[1] : false,
            ]);
        }
    }
    
    /**
     * @param string $tableName
     * @param array $indexes format: [column, unique?, name?] (? are optionall)
     * @throws \UnexpectedValueException
     * @return void
     */
    final protected function dropIndexes($tableName, array $indexes)
    {
        foreach ($indexes as $index) {
            $this->dropIndexByName($tableName, $this->createIndexName($tableName, $index));
        }
    }
    
    /**
     * @param string $tableName
     * @param array $fk format: [col, table, tablecol, ondelete?, onupdate?, fkname?] (? are optionall)
     * @return string
     */
    private function createForeginKeyName($tableName, array $fk)
    {
        if (isset($fk[5])) {
            return $fk[5];
        }
        
        return $this->keepNameShort('fk', $tableName, $fk[0], '');
    }
    
    /**
     * @param string $tableName
     * @param array $index format: [column, unique?, name?] (? are optionall); column can be array (if multi-col index)
     * @return string
     */
    private function createIndexName($tableName, array $index)
    {
        if (isset($index[2])) {
            return $index[2];
        }
        
        if (is_array($index[0])) {
            //this is a multi-columns index
            return $this->keepNameShort('', $tableName, implode('_', $index[0]), 'idx');
        }
        
        return $this->keepNameShort('', $tableName, $index[0], 'idx');
    }
    
    /**
     * @param string $tableName
     * @param string $pkName
     * @return string
     */
    final protected function createSequenceName($tableName, $pkName)
    {
        return $this->keepNameShort('', $tableName, $pkName, 'seq');
    }
    
    /**
     * @param string $prefix can be empty
     * @param string $tableName should not be empty
     * @param string $itemName should not be empty
     * @param string $suffix can be empty
     * @param int $maxLength
     * @throws \LogicException
     * @return string
     */
    private function keepNameShort($prefix, $tableName, $itemName, $suffix, $maxLength = 63)
    {
        $name = implode('_', array_filter([$prefix, $tableName, $itemName, $suffix]));
    
        if (strlen($name) > $maxLength) {
        
            $addonsLength = array_reduce(array_filter([$prefix, $suffix]), function ($result, $word) {
                return $result + strlen($word) + 1;
            }, 0);
        
            if ($addonsLength >= $maxLength) {
                throw new \LogicException('Too long prefix and/or suffix');
            }
        
            $maxItemLength = $maxLength - $addonsLength;
            $itemLength = strlen($itemName);
        
            if ($itemLength <= $maxItemLength) {
                $name = implode('_', array_filter([$prefix, $itemName, $suffix]));
                $maxTableLength = $maxLength - strlen($name) - 1;
                if ($maxTableLength > 0) {
                    $name = implode('_', array_filter([$prefix, substr($tableName, 0, $maxTableLength), $itemName, $suffix]));
                }
            } else {
                $name = implode('_', array_filter([$prefix, substr($itemName, 0, $maxItemLength - $itemLength), $suffix]));
            }
        
            if (strlen($name) > $maxLength) {
                $name = substr($name, 0, $maxLength);
            }
        }
    
        return $name;
    }
}