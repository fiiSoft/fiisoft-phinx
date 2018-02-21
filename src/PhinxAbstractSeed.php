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
        list($tableName, $options) = $this->prepareTableNameAndOptions($tableName, $options);
        $table = parent::table($tableName, $options);
    
        $schema = isset($options['schema']) ? $options['schema'] : 'public';
        $table->setAdapter($this->getAdapterWithSchema($table->getAdapter(), $schema));
    
        return $table;
    }
}