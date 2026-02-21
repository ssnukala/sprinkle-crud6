<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Bakery;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use UserFrosting\Config\Config;
use UserFrosting\Sprinkle\CRUD6\Bakery\Helper\CommandConfigTrait;
use UserFrosting\Sprinkle\CRUD6\Bakery\Helper\DatabaseScanner;
use UserFrosting\Sprinkle\CRUD6\Bakery\Helper\SchemaGenerator;

/**
 * Generate Schema Command
 *
 * CLI command to generate CRUD6 schema files from database tables.
 *
 * @author Srinivas Nukala
 */
class GenerateSchemaCommand extends Command
{
    use CommandConfigTrait;
    /**
     * @var DatabaseScanner
     */
    protected DatabaseScanner $scanner;

    /**
     * @var SchemaGenerator
     */
    protected SchemaGenerator $generator;

    /**
     * @var Config
     */
    protected Config $config;

    /**
     * Constructor.
     *
     * @param DatabaseScanner $scanner Database scanner service
     * @param SchemaGenerator $generator Schema generator service
     * @param Config $config Configuration service
     */
    public function __construct(DatabaseScanner $scanner, SchemaGenerator $generator, Config $config)
    {
        parent::__construct();
        $this->scanner = $scanner;
        $this->generator = $generator;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('crud6:generate')
            ->setDescription('Generate CRUD6 schema files from database tables')
            ->setHelp('This command scans a database and generates CRUD6 schema files based on the sprinkle-crud6 pattern')
            ->addOption(
                'database',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Database connection name to scan (default: default connection)',
                ''
            )
            ->addOption(
                'tables',
                't',
                InputOption::VALUE_OPTIONAL,
                'Comma-separated list of tables to generate schemas for (default: all tables)',
                ''
            )
            ->addOption(
                'output-dir',
                'r',
                InputOption::VALUE_REQUIRED,
                'Directory to save generated schema files (overrides config)',
                ''
            )
            ->addOption(
                'no-create',
                null,
                InputOption::VALUE_NONE,
                'Disable CREATE operation'
            )
            ->addOption(
                'no-update',
                null,
                InputOption::VALUE_NONE,
                'Disable UPDATE operation'
            )
            ->addOption(
                'no-delete',
                null,
                InputOption::VALUE_NONE,
                'Disable DELETE operation'
            )
            ->addOption(
                'no-list',
                null,
                InputOption::VALUE_NONE,
                'Disable LIST operation'
            )
            ->addOption(
                'detect-implicit',
                'i',
                InputOption::VALUE_NONE,
                'Detect implicit foreign key relationships based on naming conventions'
            )
            ->addOption(
                'sample-size',
                's',
                InputOption::VALUE_OPTIONAL,
                'Number of rows to sample for relationship validation (0 to disable, default: 100)',
                '100'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('CRUD6 Schema Generator');

        try {
            // Configure database connection
            $databaseConnection = $this->configureDatabaseConnection($input, $io, $this->scanner);

            // Scan and filter tables
            $tablesMetadata = $this->scanFilteredTables($input, $io, $this->scanner, $this->config);
            if (empty($tablesMetadata)) {
                return Command::SUCCESS;
            }

            // Detect relationships using config + CLI options
            $relationships = $this->detectConfiguredRelationships($input, $io, $this->scanner, $this->config, $tablesMetadata);

            // Handle CRUD options - merge config with command line options
            $configCrudOptions = $this->config->get('crud6.crud_options', []);
            $crudOptions = [
                'create' => !$input->getOption('no-create') && ($configCrudOptions['create'] ?? true),
                'read' => true, // Always enabled
                'update' => !$input->getOption('no-update') && ($configCrudOptions['update'] ?? true),
                'delete' => !$input->getOption('no-delete') && ($configCrudOptions['delete'] ?? true),
                'list' => !$input->getOption('no-list') && ($configCrudOptions['list'] ?? true),
            ];

            // Get output directory - command line option overrides config
            $outputDir = $input->getOption('output-dir');
            if (empty($outputDir)) {
                $outputDir = $this->config->get('crud6.schema_directory', 'app/schema/crud6');
            }

            // If database connection is specified, append it as a subfolder
            if (!empty($databaseConnection)) {
                $outputDir = rtrim($outputDir, '/') . '/' . $databaseConnection;
            }

            // Create a new generator with the command-specific options if needed
            if ($crudOptions !== $configCrudOptions || $outputDir !== $this->generator->getSchemaDirectory()) {
                $generator = new SchemaGenerator($outputDir, $crudOptions);
            } else {
                $generator = $this->generator;
            }

            // Generate schemas using two-phase approach
            $io->section('Generating Schema Files...');
            $generatedFiles = [];
            $failedTables = [];

            try {
                // Use the two-phase generateSchemas method which:
                // 1. Generates all schemas and stores in memory
                // 2. Updates detail sections with actual list_fields from related schemas
                // 3. Writes all JSON files at the end
                $filePaths = $generator->generateSchemas($tablesMetadata, $relationships);

                foreach ($filePaths as $filePath) {
                    $tableName = basename($filePath, '.json');
                    $generatedFiles[] = ['table' => $tableName, 'file' => $filePath];
                    $io->writeln(sprintf('  Generated schema for table: %s <info>✓</info>', $tableName));
                }
            } catch (\Exception $e) {
                $io->writeln('<error>✗</error>');
                $failedTables[] = ['table' => 'all', 'error' => $e->getMessage()];
            }

            // Display results
            if (!empty($generatedFiles)) {
                $io->success(sprintf('Generated %d schema file(s) in: %s', count($generatedFiles), $outputDir));

                // Format output to show table names prominently
                $tableList = array_map(function ($item) {
                    return sprintf('%s → %s', $item['table'], basename($item['file']));
                }, $generatedFiles);
                $io->listing($tableList);
            }

            // Display failed tables if any
            if (!empty($failedTables)) {
                $io->warning(sprintf('%d table(s) failed to generate schemas', count($failedTables)));
                foreach ($failedTables as $failed) {
                    $io->error(sprintf('Table: %s - Error: %s', $failed['table'], $failed['error']));
                }
            }

            // Display CRUD options
            if (!empty($generatedFiles)) {
                $io->section('CRUD Options Applied:');
                $optionsTable = [];
                foreach ($crudOptions as $operation => $enabled) {
                    $optionsTable[] = [
                        ucfirst($operation),
                        $enabled ? '✓ Enabled' : '✗ Disabled',
                    ];
                }
                $io->table(['Operation', 'Status'], $optionsTable);
            }

            return empty($failedTables) ? Command::SUCCESS : Command::FAILURE;
        } catch (\Exception $e) {
            $io->error('Error generating schemas: ' . $e->getMessage());
            $io->text('Stack trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}