<?php

namespace FiiSoft\Phinx;

use FiiSoft\Tools\Configuration\AbstractConfiguration;

final class PhinxConfig extends AbstractConfiguration
{
    /**
     * Identifiers of available configs for different databases.
     *
     * @var string[]
     */
    public $configs = [];
    
    /**
     * Whole configuration.
     *
     * @var array
     */
    public $settings;
    
    /**
     * Path fo template file for created migration classes.
     *
     * @var string|null
     */
    public $template;
    
    /**
     * @param array $settings
     * @param bool $allowNulls
     */
    public function __construct(array $settings = [], $allowNulls = false)
    {
        if (isset($settings['defaults'])) {
            $defaults = $settings['defaults'];
            unset($settings['defaults']);
        } else {
            $defaults = [];
        }
        
        $this->settings = $settings;
        
        if (isset($settings['configs'])) {
            $this->configs = $settings['configs'];
            unset($settings['configs']);
        } else {
            $this->configs = array_keys($settings);
        }
    
        if (isset($defaults['template'])) {
            $this->template = $defaults['template'];
            unset($defaults['template']);
        }
        
        parent::__construct($settings, $allowNulls);
        
        $this->prepareSettings($defaults);
    }
    
    /**
     * @param array $defaults
     * @return void
     */
    private function prepareSettings(array $defaults)
    {
        $defaults = array_merge([
            'migration_base_class' => PhinxAbstractMigration::class,
        ], $defaults);
        
        $defaultItems1 = ['migration_base_class', 'version_order'];
        $defaultItems2 = ['default_database', 'default_migration_table'];
        $defaultItems3 = [
            'adapter', 'user', 'pass', 'host', 'name', 'port', 'charset', 'collation',
            'connection', 'table_prefix', 'table_suffix', 'unix_socket',
        ];
        
        foreach ($this->settings as $key => $config) {
            if (!isset($config['environments'])) {
                continue;
            }
            
            if (!isset($config['paths']) && isset($defaults['paths'])) {
                $config['paths'] = $defaults['paths'];
            }
            
            if (!isset($config['paths']['migrations'], $config['paths']['seeds'])) {
                if (isset($config['paths_root'])) {
                    $pathsRoot = $config['paths_root'];
                } elseif (isset($defaults['phinx_files'])) {
                    $pathsRoot = $defaults['phinx_files'] . DIRECTORY_SEPARATOR . $key;
                } else {
                    $pathsRoot = (getcwd() ?: '') . DIRECTORY_SEPARATOR . 'phinx' . DIRECTORY_SEPARATOR . $key;
                }
    
                foreach (['migrations', 'seeds'] as $item) {
                    if (!isset($config['paths'][$item])) {
                        $this->settings[$key]['paths'][$item] = $pathsRoot . DIRECTORY_SEPARATOR . $item;
                    }
                }
            }
            
            foreach ($defaultItems1 as $item) {
                if (!isset($config[$item]) && isset($defaults[$item])) {
                    $this->settings[$key][$item] = $defaults[$item];
                }
            }
    
            foreach ($defaultItems2 as $item) {
                if (!isset($config['environments'][$item]) && isset($defaults[$item])) {
                    $this->settings[$key]['environments'][$item] = $defaults[$item];
                }
            }
    
            foreach ($config['environments'] as $env => $environment) {
                if (is_array($environment)) {
                    foreach ($defaultItems3 as $item) {
                        if (!isset($environment[$item]) && isset($defaults[$item])) {
                            $this->settings[$key]['environments'][$env][$item] = $defaults[$item];
                        }
                    }
                }
            }
        }
    }
    
    /**
     * @param string $configName
     * @return bool
     */
    public function hasConfig($configName)
    {
        return in_array($configName, $this->configs, true);
    }
}