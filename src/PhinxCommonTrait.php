<?php

namespace FiiSoft\Phinx;

use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Db\Table;
use Symfony\Component\Console\Output\OutputInterface;
use UnexpectedValueException;

trait PhinxCommonTrait
{
    /**
     * @param Table|string $tableName
     * @param array $options
     * @throws \UnexpectedValueException
     * @return Table
     */
    final protected function getTable($tableName, array $options = [])
    {
        if ($tableName instanceof Table) {
            return $tableName;
        }
        
        list($schemaName, $tableName) = $this->getSchemaAndTableNameFrom($tableName);
        $this->writelnVerbose('Table name: '.$tableName.' and schema name: '.$schemaName);
        
        $options['schema'] = $schemaName;
        
        return $this->table($tableName, $options);
    }
    
    /**
     * @param string $tableName
     * @throws \UnexpectedValueException
     * @return array tuple with (schemaName, tableName)
     */
    private function getSchemaAndTableNameFrom($tableName)
    {
        if (false !== ($pos = strpos($tableName, '.'))) {
            $fullName = $tableName;
            
            $schemaName = substr($fullName, 0, $pos);
            $tableName = substr($fullName, $pos + 1);
            
            if ($tableName === false) {
                throw new UnexpectedValueException('Error while retrieving schema and table name from '.$fullName);
            }
        } else {
            $schemaName = 'public';
        }
        
        return [$schemaName, $tableName];
    }
    
    /**
     * @param Table|string $tableName
     * @param array|string $column
     * @param mixed|null $value
     * @throws \UnexpectedValueException
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
                if ($val === null) {
                    $pieces[] = $adapter->quoteColumnName($col).' IS NULL';
                } else {
                    $pieces[] = $adapter->quoteColumnName($col).' = '.$this->quote($val);
                }
            }
            
            return implode(' AND ', $pieces);
        }
    
        if ($value === null) {
            return $adapter->quoteColumnName($column).' IS NULL';
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
     * @param string $message
     * @return void
     */
    private function writelnVerbose($message)
    {
        $this->output->writeln($message, OutputInterface::OUTPUT_NORMAL | OutputInterface::VERBOSITY_VERBOSE);
    }
}