<?php

namespace FiiSoft\Phinx;

use Phinx\Db\Table;
use Phinx\Seed\AbstractSeed;

abstract class PhinxAbstractSeed extends AbstractSeed
{
    use PhinxCommonTrait;
    
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
}