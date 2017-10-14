<?php

namespace FiiSoft\Phinx;

final class PhinxTemplatePath
{
    /**
     * @return string full path to default template for new migration classes
     */
    public static function path()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'Migration.template.php.dist';
    }
    
    /**
     * @return string full directory (with trailing slash) to where default template for migration classes is stored
     */
    public static function dir()
    {
        return __DIR__ . DIRECTORY_SEPARATOR;
    }
    
    /**
     * @return string name of default template for migration classes
     */
    public static function file()
    {
        return 'Migration.template.php.dist';
    }
}