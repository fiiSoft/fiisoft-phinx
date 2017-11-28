<?php

namespace FiiSoft\Phinx;

use Phinx\Config\Config;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

trait PhinxCmdExecuteTrait
{
    /** @var PhinxConfig */
    private $phinxConfig;
    
    /**
     * {@inheritdoc}
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this->addArgument(
            'config',
            InputArgument::REQUIRED,
            'Name of configuration, one of: '.implode(', ', $this->phinxConfig->configs)
        );
    
        parent::configure();
        
        $this->setName('phinx:'.$this->getName());
        
        /* @var $definition InputDefinition */
        $definition = $this->getDefinition();
        
        if ($definition->hasOption('environment')) {
            $this->addArgument('environment', InputArgument::OPTIONAL, 'Environment (if not default)');
        }
    
        $definition->setOptions(array_filter($definition->getOptions(), function (InputOption $option) {
            return !in_array($option->getName(), ['configuration', 'parser'], true);
        }));
    }

    /**
     * Show the migration status.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @return integer 0 if all migrations are up, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (0 !== ($exitCode = $this->setCommandPhinxConfig($input, $output))) {
            return $exitCode;
        }
        
        $this->loadManager($input, $output);
        
        return parent::execute($input, $output);
    }
    
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @return int
     */
    private function setCommandPhinxConfig(InputInterface $input, OutputInterface $output)
    {
        $configName = $input->getArgument('config');
        
        if (!$this->phinxConfig->hasConfig($configName)) {
            $output->writeln('<error>Configuration '.$configName.' not found</error>');
            return 1;
        }
        
        $env = $input->hasArgument('environment') ? $input->getArgument('environment') : null;
        
        if (!$env && $input->hasOption('environment')) {
            $env = $input->getOption('environment');
        }
        
        $config = $this->phinxConfig->settings[$configName];
        
        if ($env) {
            if (!isset($config['environments'][$env])) {
                $fullEnv = $configName.'_'.$env;
                if (isset($config['environments'][$fullEnv])) {
                    $env = $fullEnv;
                }
            }
            
            $input->setOption('environment', $env);
        }
    
        if ($input->hasOption('template') && !$input->getOption('template') && $this->phinxConfig->template !== null) {
            $input->setOption('template', $this->phinxConfig->template);
        }
        
        $this->setConfig(new Config($config));
        
        return 0;
    }
    
    /**
     * Parse the config file and load it into the config object
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \InvalidArgumentException
     * @return void
     */
    protected function loadConfig(InputInterface $input, OutputInterface $output)
    {
        //do nothing
    }
}