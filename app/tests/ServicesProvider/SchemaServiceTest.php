<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Tests\ServicesProvider;

use PHPUnit\Framework\TestCase;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;
use UserFrosting\UniformResourceLocator\ResourceLocatorInterface;

/**
 * Schema Service Test
 *
 * Tests the SchemaService class functionality with ResourceLocatorInterface
 * following UserFrosting 6 patterns.
 */
class SchemaServiceTest extends TestCase
{
    /**
     * Test SchemaService construction with all required dependencies
     */
    public function testSchemaServiceConstruction(): void
    {
        // Create all required mocks for SchemaService constructor (11 parameters)
        $locator = $this->createMock(ResourceLocatorInterface::class);
        $config = $this->createMock(\UserFrosting\Config\Config::class);
        $logger = $this->createMock(\UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface::class);
        $i18n = $this->createMock(\UserFrosting\I18n\Translator::class);
        $loader = $this->createMock(\UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaLoader::class);
        $validator = $this->createMock(\UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaValidator::class);
        $normalizer = $this->createMock(\UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaNormalizer::class);
        $cache = $this->createMock(\UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaCache::class);
        $filter = $this->createMock(\UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaFilter::class);
        $translator = $this->createMock(\UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaTranslator::class);
        $actionManager = $this->createMock(\UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaActionManager::class);
        
        // This should not throw an exception
        $schemaService = new SchemaService(
            $locator,
            $config,
            $logger,
            $i18n,
            $loader,
            $validator,
            $normalizer,
            $cache,
            $filter,
            $translator,
            $actionManager
        );
        
        $this->assertInstanceOf(SchemaService::class, $schemaService);
    }
    
    /**
     * Note: Tests for private/protected methods (getSchemaFilePath, applyDefaults, normalizeBooleanTypes, etc.)
     * have been moved to dedicated test classes for the individual service components:
     * - SchemaLoaderTest - tests SchemaLoader::getSchemaFilePath() and applyDefaults()
     * - SchemaNormalizerTest - tests SchemaNormalizer::normalizeBooleanTypes() and other normalizations
     * - SchemaValidatorTest - tests SchemaValidator::validate() and hasPermission()
     * 
     * The SchemaService class now orchestrates these services through its public API methods
     * (getSchema, clearCache, filterSchemaForContext, etc.)
     */
}