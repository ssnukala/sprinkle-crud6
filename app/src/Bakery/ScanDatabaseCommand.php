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

/**
 * Scan Database Command
 *
 * CLI command to scan a database and display its structure.
 *
 * @author Srinivas Nukala
 */
class ScanDatabaseCommand extends Command
{
    use CommandConfigTrait;
    /**
     * @var DatabaseScanner
     */
    protected DatabaseScanner $scanner;

    /**
     * @var Config
     */
    protected Config $config;

    /**
     * Constructor.
     *
     * @param DatabaseScanner $scanner Database scanner service
     * @param Config $config Configuration service
     */
    public function __construct(DatabaseScanner $scanner, Config $config)
    {
        parent::__construct();
        $this->scanner = $scanner;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('crud6:scan')
            ->setDescription('Scan database and display table structure')
            ->setHelp('This command scans a database and displays all tables, columns, and relationships')
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
                'Comma-separated list of tables to scan (default: all tables)',
                ''
            )
            ->addOption(
                'output',
                'o',
                InputOption::VALUE_OPTIONAL,
                'Output format (table, json)',
                'table'
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

        $io->title('Database Scanner');

        try {
            // Configure database connection
            $this->configureDatabaseConnection($input, $io, $this->scanner);

            // Scan and filter tables
            $tablesMetadata = $this->scanFilteredTables($input, $io, $this->scanner, $this->config);
            if (empty($tablesMetadata)) {
                return Command::SUCCESS;
            }

            // Detect relationships using config + CLI options
            $relationships = $this->detectConfiguredRelationships($input, $io, $this->scanner, $this->config, $tablesMetadata);

            // Output results
            $outputFormat = $input->getOption('output');

            if ($outputFormat === 'json') {
                $output->writeln(json_encode([
                    'tables' => $tablesMetadata,
                    'relationships' => $relationships,
                ], JSON_PRETTY_PRINT));
            } else {
                $this->displayTablesInfo($io, $tablesMetadata, $relationships);
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error scanning database: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Display tables information in console.
     *
     * @param SymfonyStyle $io Console style
     * @param array $tablesMetadata Tables metadata
     * @param array $relationships Relationships
     */
    protected function displayTablesInfo(SymfonyStyle $io, array $tablesMetadata, array $relationships): void
    {
        foreach ($tablesMetadata as $tableName => $metadata) {
            $io->section("Table: {$tableName}");

            // Display columns
            $columnRows = [];
            foreach ($metadata['columns'] as $column) {
                $columnRows[] = [
                    $column['name'],
                    $column['type'],
                    $column['nullable'] ? 'YES' : 'NO',
                    $column['default'] ?? '',
                    $column['autoincrement'] ? 'YES' : 'NO',
                ];
            }

            $io->table(
                ['Column', 'Type', 'Nullable', 'Default', 'Auto Increment'],
                $columnRows
            );

            // Display primary key
            if (!empty($metadata['primaryKey'])) {
                $io->text('Primary Key: ' . implode(', ', $metadata['primaryKey']));
            }

            // Display foreign keys
            if (!empty($metadata['foreignKeys'])) {
                $io->text('Foreign Keys:');
                foreach ($metadata['foreignKeys'] as $fk) {
                    $io->text(sprintf(
                        '  - %s (%s) -> %s (%s)',
                        implode(', ', $fk['localColumns']),
                        $tableName,
                        $fk['foreignTable'],
                        implode(', ', $fk['foreignColumns'])
                    ));
                }
            }

            // Display relationships
            if (isset($relationships[$tableName])) {
                $rels = $relationships[$tableName];

                if (!empty($rels['references'])) {
                    $io->text('References (Foreign Keys):');
                    foreach ($rels['references'] as $rel) {
                        $relationshipType = $rel['type'] ?? 'explicit';
                        $confidenceInfo = '';

                        if ($relationshipType === 'implicit' && isset($rel['confidence'])) {
                            $confidencePercent = round($rel['confidence'] * 100, 1);
                            $confidenceInfo = sprintf(' [implicit, confidence: %s%%]', $confidencePercent);
                        }

                        $io->text(sprintf(
                            '  - %s (via %s -> %s)%s',
                            $rel['table'],
                            $rel['localKey'],
                            $rel['foreignKey'],
                            $confidenceInfo
                        ));
                    }
                }

                // Find tables that reference this table
                $referencedBy = [];
                foreach ($relationships as $otherTableName => $otherRels) {
                    if (!empty($otherRels['references'])) {
                        foreach ($otherRels['references'] as $ref) {
                            if ($ref['table'] === $tableName) {
                                $referencedBy[] = [
                                    'table' => $otherTableName,
                                    'foreignKey' => $ref['localKey'],
                                    'localKey' => $ref['foreignKey'],
                                ];
                            }
                        }
                    }
                }

                if (!empty($referencedBy)) {
                    $io->text('Referenced By:');
                    foreach ($referencedBy as $rel) {
                        $io->text(sprintf(
                            '  - %s (via %s -> %s)',
                            $rel['table'],
                            $rel['foreignKey'],
                            $rel['localKey']
                        ));
                    }
                }
            }

            $io->newLine();
        }
    }
}