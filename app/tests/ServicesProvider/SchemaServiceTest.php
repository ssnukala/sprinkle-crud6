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
     * Test SchemaService construction with ResourceLocatorInterface
     */
    public function testSchemaServiceConstruction(): void
    {
        // Create a mock ResourceLocatorInterface
        $locator = $this->createMock(ResourceLocatorInterface::class);
        $config = $this->createMock(\UserFrosting\Config\Config::class);
        
        // This should not throw an exception
        $schemaService = new SchemaService($locator, $config);
        
        $this->assertInstanceOf(SchemaService::class, $schemaService);
    }
    
    /**
     * Note: Tests for private/protected methods (getSchemaFilePath, applyDefaults, normalizeBooleanTypes, etc.)
     * have been moved to dedicated test classes for the individual service components:
     * - SchemaLoaderTest - tests SchemaLoader::getSchemaFilePath() and applyDefaults()
     * - SchemaNormalizerTest - tests SchemaNormalizer::normalizeBooleanTypes() and other normalizations
     * - SchemaValidatorTest - tests SchemaValidator::validate() and hasPermission()
     * 
     * The SchemaService class now orchestrates these services and maintains backward compatibility
     * through its public API methods (getSchema, clearCache, filterSchemaForContext, etc.)
     */
}