<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Bakery;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use UserFrosting\Sprinkle\CRUD6\Schema\SchemaGenerator;

/**
 * Bakery command to generate CRUD6 schemas and translations.
 * 
 * This command provides a convenient way to generate schema JSON files
 * and corresponding locale translations for CRUD6 models.
 * 
 * Usage:
 *   php bakery crud6:generate-schema
 *   php bakery crud6:generate-schema --schema-dir=path/to/schema
 * 
 * Other sprinkles can use this to generate their own schemas programmatically.
 */
class GenerateSchemaCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('crud6:generate-schema')
            ->setDescription('Generate CRUD6 schema files and translations')
            ->setHelp('This command generates schema JSON files and locale translations for CRUD6 models.')
            ->addOption(
                'schema-dir',
                's',
                InputOption::VALUE_OPTIONAL,
                'Directory for schema files',
                'app/schema/crud6'
            )
            ->addOption(
                'locale-dir',
                'l',
                InputOption::VALUE_OPTIONAL,
                'Directory for locale files',
                'app/locale/en_US'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force regeneration even if files exist'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('CRUD6 Schema Generator');
        
        $schemaDir = $input->getOption('schema-dir');
        $localeDir = $input->getOption('locale-dir');
        $force = $input->getOption('force');
        
        $io->section('Configuration');
        $io->listing([
            "Schema Directory: {$schemaDir}",
            "Locale Directory: {$localeDir}",
            "Force: " . ($force ? 'Yes' : 'No'),
        ]);
        
        // Check if we should proceed
        if (!$force && $this->filesExist($schemaDir)) {
            if (!$io->confirm('Schema files already exist. Overwrite?', false)) {
                $io->warning('Operation cancelled.');
                return Command::SUCCESS;
            }
        }
        
        try {
            $io->section('Generating Schemas');
            
            // Use SchemaGenerator to generate files
            SchemaGenerator::generateToPath($schemaDir, $localeDir);
            
            $io->success('Schema files and translations generated successfully!');
            
            $io->section('Generated Files');
            $io->listing([
                "Schemas: {$schemaDir}/*.json",
                "Translations: {$localeDir}/messages.php",
            ]);
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $io->error('Failed to generate schemas: ' . $e->getMessage());
            if ($output->isVerbose()) {
                $io->text($e->getTraceAsString());
            }
            return Command::FAILURE;
        }
    }
    
    /**
     * Check if schema files already exist.
     * 
     * @param string $schemaDir Schema directory path
     * 
     * @return bool True if files exist
     */
    private function filesExist(string $schemaDir): bool
    {
        return is_dir($schemaDir) && count(glob($schemaDir . '/*.json')) > 0;
    }
}
