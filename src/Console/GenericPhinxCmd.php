<?php

namespace FiiSoft\Phinx\Console;

use FiiSoft\Phinx\PhinxConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class GenericPhinxCmd extends Command
{
    const CREATES_CMDS = ['create', 'seed:create'];
    
    /** @var PhinxConfig */
    private $phinxConfig;
    
    /**
     * @param PhinxConfig $config
     * @param string|null $name
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(PhinxConfig $config, $name = null)
    {
        $this->phinxConfig = $config;
        
        parent::__construct($name ?: 'phinx');
    }
    
    /**
     * {@inheritdoc}
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this->setDescription('Run particular Phinx command');
        
        $this->addArgument(
            'config',
            InputArgument::REQUIRED,
            'Name of configuration, one of: '.implode(', ', $this->phinxConfig->configs)
        );
        
        $this->addArgument('environment', InputArgument::OPTIONAL, 'Environment (if not default)');
        $this->addArgument('action', InputArgument::OPTIONAL, 'Action: status, create, migrate, rollback, etc.');
        $this->addArgument('name', InputArgument::OPTIONAL, 'Name of migration or seed to create (CamelCase)');
        
        $this->addOption('environment', 'e', InputOption::VALUE_REQUIRED, 'Environment (if not default)');
    }
    
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Console\Exception\LogicException
     * @throws \Exception
     * @return int status of operation
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configName = $input->getArgument('config');
        if (!$this->phinxConfig->hasConfig($configName)) {
            $output->writeln('<error>Configuration '.$configName.' not found</error>');
            return 1;
        }
        
        $action = $input->getArgument('action');
        
        $env = $input->getArgument('environment');
        if ($env) {
            if (!$action) {
                if ($input->getOption('environment')) {
                    $action = $env;
                    $env = $input->getOption('environment');
                } else {
                    $action = $env;
                    $env = null;
                }
            } elseif (in_array($env, self::CREATES_CMDS, true)) {
                if ($input->getOption('environment')) {
                    $input->setArgument('name', $action);
                    $action = $env;
                    $env = $input->getOption('environment');
                } elseif (!$input->getArgument('name')) {
                    $input->setArgument('name', $action);
                    $action = $env;
                    $env = null;
                }
            }
        } else {
            $action = 'status';
        }
        
        $output->writeln('Use configuration <info>'.$configName.'</info>');
        
        if ($env) {
            $output->writeln('Use environment <info>'.$env.'</info>');
        }
        
        $output->writeln('Call action <info>'.$action.'</info>');
        
        switch ($action) {
            case 'breakpoint':
                $cmd = new PhinxBreakpointCmd($this->phinxConfig);
            break;
            case 'create':
                $cmd = new PhinxCreateCmd($this->phinxConfig);
            break;
            case 'migrate':
                $cmd = new PhinxMigrateCmd($this->phinxConfig);
            break;
            case 'rollback':
                $cmd = new PhinxRollbackCmd($this->phinxConfig);
            break;
            case 'seed:create':
                $cmd = new PhinxSeedCreateCmd($this->phinxConfig);
            break;
            case 'seed:run':
                $cmd = new PhinxSeedRunCmd($this->phinxConfig);
            break;
            case 'status':
                $cmd = new PhinxStatusCmd($this->phinxConfig);
            break;
            case 'test':
                $cmd = new PhinxTestCmd($this->phinxConfig);
            break;
            default:
                $output->writeln('<error>Invalid action: '.$action.'</error>');
                return 1;
        }
    
        $args = [
            'config' => $configName,
        ];
    
        if ($cmd->getDefinition()->hasArgument('name')) {
            $args['name'] = $input->getArgument('name');
        }
    
        if ($cmd->getDefinition()->hasOption('environment')) {
            $args['environment'] = $env;
        }
    
        return $cmd->run(new ArrayInput($args), $output);
    }
}