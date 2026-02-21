<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Bakery\Helper;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use UserFrosting\Config\Config;

/**
 * Shared configuration logic for CRUD6 bakery commands.
 *
 * Extracts the common database connection setup, table filtering,
 * exclusion list, and relationship detection configuration that is
 * used by both ScanDatabaseCommand and GenerateSchemaCommand.
 *
 * @author Srinivas Nukala
 */
trait CommandConfigTrait
{
    /**
     * Configure the database connection from command input.
     *
     * @param InputInterface $input CLI input
     * @param SymfonyStyle $io Console output
     * @param DatabaseScanner $scanner Scanner instance to configure
     * @return string Database connection name (empty string for default)
     */
    protected function configureDatabaseConnection(
        InputInterface $input,
        SymfonyStyle $io,
        DatabaseScanner $scanner
    ): string {
        $databaseConnection = $input->getOption('database');
        if (!empty($databaseConnection)) {
            $scanner->setConnection($databaseConnection);
            $io->note(sprintf('Using database connection: %s', $databaseConnection));
        } else {
            $io->note('Using default database connection');
        }
        return $databaseConnection;
    }

    /**
     * Scan database tables with filtering and exclusions applied.
     *
     * @param InputInterface $input CLI input
     * @param SymfonyStyle $io Console output
     * @param DatabaseScanner $scanner Scanner instance
     * @param Config $config Configuration service
     * @return array Filtered table metadata (empty if no tables found)
     */
    protected function scanFilteredTables(
        InputInterface $input,
        SymfonyStyle $io,
        DatabaseScanner $scanner,
        Config $config
    ): array {
        // Get table filter from --tables option
        $tableFilter = [];
        $tablesOption = $input->getOption('tables');
        if (!empty($tablesOption)) {
            $tableFilter = array_map('trim', explode(',', $tablesOption));
        }

        // Get exclude tables from config
        $excludeTables = $config->get('crud6.exclude_tables', []);

        // Scan database
        $io->section('Scanning Database...');
        $tablesMetadata = $scanner->scanDatabase($tableFilter);

        // Apply exclusions
        foreach ($excludeTables as $excludeTable) {
            unset($tablesMetadata[$excludeTable]);
        }

        if (empty($tablesMetadata)) {
            $io->warning('No tables found in the database.');
        } else {
            $io->success(sprintf('Found %d table(s)', count($tablesMetadata)));
        }

        return $tablesMetadata;
    }

    /**
     * Configure relationship detection from config and CLI options, then detect relationships.
     *
     * @param InputInterface $input CLI input
     * @param SymfonyStyle $io Console output
     * @param DatabaseScanner $scanner Scanner instance
     * @param Config $config Configuration service
     * @param array $tablesMetadata Table metadata from scanFilteredTables()
     * @return array Detected relationships
     */
    protected function detectConfiguredRelationships(
        InputInterface $input,
        SymfonyStyle $io,
        DatabaseScanner $scanner,
        Config $config,
        array $tablesMetadata
    ): array {
        $relationshipConfig = $config->get('crud6.relationship_detection', []);
        $detectImplicit = $input->getOption('detect-implicit') ?: ($relationshipConfig['detect_implicit'] ?? false);
        $sampleSize = (int) $input->getOption('sample-size');

        // If sample size is still the default, use config value
        if ($sampleSize === 100) {
            $sampleSize = $relationshipConfig['sample_size'] ?? 100;
        }

        // Configure scanner with config values
        if (isset($relationshipConfig['naming_patterns'])) {
            $scanner->setNamingPatterns($relationshipConfig['naming_patterns']);
        }
        if (isset($relationshipConfig['table_prefixes'])) {
            $scanner->setTablePrefixes($relationshipConfig['table_prefixes']);
        }
        if (isset($relationshipConfig['confidence_threshold'])) {
            $scanner->setConfidenceThreshold($relationshipConfig['confidence_threshold']);
        }

        if ($detectImplicit) {
            $io->note(sprintf(
                'Detecting implicit relationships with sampling (sample size: %d)',
                $sampleSize
            ));
        }

        return $scanner->detectRelationships($tablesMetadata, $detectImplicit, $sampleSize);
    }
}
