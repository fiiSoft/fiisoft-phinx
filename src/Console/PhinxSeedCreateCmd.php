<?php

namespace FiiSoft\Phinx\Console;

use FiiSoft\Phinx\PhinxAbstractSeed;
use FiiSoft\Phinx\PhinxCmdExecuteTrait;
use FiiSoft\Phinx\PhinxConfig;
use Phinx\Config\NamespaceAwareInterface;
use Phinx\Console\Command\SeedCreate;
use Phinx\Util\Util;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class PhinxSeedCreateCmd extends SeedCreate
{
    use PhinxCmdExecuteTrait;
    
    /**
     * @param PhinxConfig $config
     * @param string|null $name
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(PhinxConfig $config, $name = null)
    {
        $this->phinxConfig = $config;
        
        parent::__construct($name);
    }
    
    /**
     * Create the new seeder.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Console\Exception\LogicException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws \Exception
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (0 !== ($exitCode = $this->setCommandPhinxConfig($input, $output))) {
            return $exitCode;
        }
    
        $this->bootstrap($input, $output);
        
        // get the seed path from the config
        $path = $this->getSeedPath($input, $output);
        
        if (!file_exists($path)) {
            $helper   = $this->getHelper('question');
            $question = $this->getCreateSeedDirectoryQuestion();
            
            if ($helper->ask($input, $output, $question)) {
                mkdir($path, 0755, true);
            }
        }
        
        $this->verifySeedDirectory($path);
        
        $path = realpath($path);
        $className = $input->getArgument('name');
        
        if (!Util::isValidPhinxClassName($className)) {
            throw new \InvalidArgumentException(sprintf(
                'The seed class name "%s" is invalid. Please use CamelCase format',
                $className
            ));
        }
        
        // Compute the file path
        $filePath = $path . DIRECTORY_SEPARATOR . $className . '.php';
        
        if (is_file($filePath)) {
            throw new \InvalidArgumentException(sprintf(
                'The file "%s" already exists',
                basename($filePath)
            ));
        }
        
        // inject the class names appropriate to this seeder
        $contents = file_get_contents($this->getSeedTemplateFilename());
        
        $config = $this->getConfig();
        $namespace = $config instanceof NamespaceAwareInterface ? $config->getSeedNamespaceByPath($path) : null;
        $classes = array(
            '$namespaceDefinition' => null !== $namespace ? ('namespace ' . $namespace . ';') : '',
            '$namespace'           => $namespace,
            '$useClassName'        => PhinxAbstractSeed::class,
            '$className'           => $className,
            '$baseClassName'       => 'PhinxAbstractSeed',
        );
        $contents = strtr($contents, $classes);
        
        if (false === file_put_contents($filePath, $contents)) {
            throw new \RuntimeException(sprintf(
                'The file "%s" could not be written to',
                $path
            ));
        }
        
        $output->writeln('<info>using seed base class</info> ' . $classes['$useClassName']);
        $output->writeln('<info>created</info> .' . str_replace(getcwd(), '', $filePath));
    }
}