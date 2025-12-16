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

/**
 * SchemaCachingContextTest
 *
 * Tests that schema caching works correctly with context-based filtering
 * to ensure no duplicate API requests are made.
 * 
 * Validates that:
 * - Different contexts for same model are cached separately
 * - Same context for same model uses cache (no duplicate requests)
 * - Cache keys include both model and context
 */
class SchemaCachingContextTest extends TestCase
{
    /**
     * Test that caching logic is context-aware
     */
    public function testStoreHasContextAwareCaching(): void
    {
        $storeFile = dirname(__DIR__, 2) . '/assets/stores/useCRUD6SchemaStore.ts';
        $this->assertFileExists($storeFile, 'useCRUD6SchemaStore.ts should exist');
        
        $storeContent = file_get_contents($storeFile);
        $this->assertNotFalse($storeContent, 'Should be able to read store file');
        
        // Should have getCacheKey function that combines model and context
        $this->assertStringContainsString('getCacheKey', $storeContent, 'Should have getCacheKey function');
        $this->assertStringContainsString('model', $storeContent, 'getCacheKey should use model');
        $this->assertStringContainsString('context', $storeContent, 'getCacheKey should use context');
        
        // Should cache by combined key, not just model
        $this->assertStringContainsString('cacheKey', $storeContent, 'Should use cacheKey for lookups');
    }

    /**
     * Test that hasSchema checks include context
     */
    public function testHasSchemaIncludesContext(): void
    {
        $storeFile = dirname(__DIR__, 2) . '/assets/stores/useCRUD6SchemaStore.ts';
        $storeContent = file_get_contents($storeFile);
        
        // hasSchema should accept context parameter
        $this->assertMatchesRegularExpression(
            '/function hasSchema\([^)]*model[^)]*context[^)]*\)/',
            $storeContent,
            'hasSchema should accept model and context parameters'
        );
    }

    /**
     * Test that getSchema checks include context
     */
    public function testGetSchemaIncludesContext(): void
    {
        $storeFile = dirname(__DIR__, 2) . '/assets/stores/useCRUD6SchemaStore.ts';
        $storeContent = file_get_contents($storeFile);
        
        // getSchema should accept context parameter
        $this->assertMatchesRegularExpression(
            '/function getSchema\([^)]*model[^)]*context[^)]*\)/',
            $storeContent,
            'getSchema should accept model and context parameters'
        );
    }

    /**
     * Test that isLoading checks include context
     */
    public function testIsLoadingIncludesContext(): void
    {
        $storeFile = dirname(__DIR__, 2) . '/assets/stores/useCRUD6SchemaStore.ts';
        $storeContent = file_get_contents($storeFile);
        
        // isLoading should accept context parameter
        $this->assertMatchesRegularExpression(
            '/function isLoading\([^)]*model[^)]*context[^)]*\)/',
            $storeContent,
            'isLoading should accept model and context parameters'
        );
    }

    /**
     * Test that getError checks include context
     */
    public function testGetErrorIncludesContext(): void
    {
        $storeFile = dirname(__DIR__, 2) . '/assets/stores/useCRUD6SchemaStore.ts';
        $storeContent = file_get_contents($storeFile);
        
        // getError should accept context parameter
        $this->assertMatchesRegularExpression(
            '/function getError\([^)]*model[^)]*context[^)]*\)/',
            $storeContent,
            'getError should accept model and context parameters'
        );
    }

    /**
     * Test that loadSchema uses cache key for storage
     */
    public function testLoadSchemaUsesCacheKey(): void
    {
        $storeFile = dirname(__DIR__, 2) . '/assets/stores/useCRUD6SchemaStore.ts';
        $storeContent = file_get_contents($storeFile);
        
        // loadSchema should call getCacheKey
        $this->assertStringContainsString('getCacheKey(model, context)', $storeContent, 
            'loadSchema should call getCacheKey with model and context');
        
        // Should use cacheKey for storing
        $this->assertStringContainsString('schemas.value[cacheKey]', $storeContent,
            'Should store schema using cacheKey');
        
        // Should use cacheKey for loading states
        $this->assertStringContainsString('loadingStates.value[cacheKey]', $storeContent,
            'Should track loading state using cacheKey');
    }

    /**
     * Test that setSchema includes context parameter
     */
    public function testSetSchemaIncludesContext(): void
    {
        $storeFile = dirname(__DIR__, 2) . '/assets/stores/useCRUD6SchemaStore.ts';
        $storeContent = file_get_contents($storeFile);
        
        // setSchema should accept context parameter
        $this->assertMatchesRegularExpression(
            '/function setSchema\([^)]*model[^)]*schemaData[^)]*context[^)]*\)/',
            $storeContent,
            'setSchema should accept model, schemaData, and context parameters'
        );
    }

    /**
     * Test that clearSchema includes context parameter
     */
    public function testClearSchemaIncludesContext(): void
    {
        $storeFile = dirname(__DIR__, 2) . '/assets/stores/useCRUD6SchemaStore.ts';
        $storeContent = file_get_contents($storeFile);
        
        // clearSchema should accept context parameter
        $this->assertMatchesRegularExpression(
            '/function clearSchema\([^)]*model[^)]*context[^)]*\)/',
            $storeContent,
            'clearSchema should accept model and context parameters'
        );
    }

    /**
     * Test that composable passes context to store
     */
    public function testComposablePassesContextToStore(): void
    {
        $composableFile = dirname(__DIR__, 2) . '/assets/composables/useCRUD6Schema.ts';
        $this->assertFileExists($composableFile, 'useCRUD6Schema.ts should exist');
        
        $composableContent = file_get_contents($composableFile);
        $this->assertNotFalse($composableContent, 'Should be able to read composable file');
        
        // Should pass context when calling store.loadSchema
        $this->assertStringContainsString('schemaStore.loadSchema(model, force, context)', $composableContent,
            'Composable should pass context to store.loadSchema');
        
        // Should pass context when calling store.getError
        $this->assertStringContainsString('schemaStore.getError(model, context)', $composableContent,
            'Composable should pass context to store.getError');
    }

    /**
     * Test that cache key format prevents collisions
     */
    public function testCacheKeyFormatPreventsCollisions(): void
    {
        $storeFile = dirname(__DIR__, 2) . '/assets/stores/useCRUD6SchemaStore.ts';
        $storeContent = file_get_contents($storeFile);
        
        // Cache key should use separator between model and context
        // Format should be: `${model}:${context || 'full'}`
        $this->assertStringContainsString('${model}:${context', $storeContent,
            'Cache key should use colon separator to prevent collisions');
        
        // Should default to 'full' when context is undefined
        $this->assertStringContainsString("|| 'full'", $storeContent,
            'Cache key should default context to "full"');
    }

    /**
     * Test documentation mentions context-aware caching
     */
    public function testDocumentationMentionsCaching(): void
    {
        $storeFile = dirname(__DIR__, 2) . '/assets/stores/useCRUD6SchemaStore.ts';
        $storeContent = file_get_contents($storeFile);
        
        // Documentation should mention context-aware caching
        $this->assertStringContainsString('cached by model name AND context', $storeContent,
            'Documentation should explain context-aware caching');
        
        // Should explain cache key format
        $this->assertStringContainsString('Cache key format', $storeContent,
            'Documentation should explain cache key format');
    }

    /**
     * Test that logging includes cache key for debugging
     */
    public function testLoggingIncludesCacheKey(): void
    {
        $storeFile = dirname(__DIR__, 2) . '/assets/stores/useCRUD6SchemaStore.ts';
        $storeContent = file_get_contents($storeFile);
        
        // Logs should include cacheKey for debugging
        $this->assertStringContainsString('cacheKey:', $storeContent,
            'Logs should include cacheKey for debugging duplicate request issues');
        
        // Should log when using cached schema
        $this->assertStringContainsString('Using cached schema', $storeContent,
            'Should log when returning cached schema');
    }

    /**
     * Test example scenario: products with different contexts
     * 
     * This validates the expected behavior:
     * 1. PageList requests products?context=list -> API call, cached as "products:list"
     * 2. PageList requests products?context=list again -> returns cache (no API call)
     * 3. PageRow requests products?context=detail -> API call, cached as "products:detail"
     * 4. PageRow requests products?context=detail again -> returns cache (no API call)
     */
    public function testExpectedCachingBehavior(): void
    {
        $storeFile = dirname(__DIR__, 2) . '/assets/stores/useCRUD6SchemaStore.ts';
        $storeContent = file_get_contents($storeFile);
        
        // Cache check should happen before API call
        $this->assertStringContainsString('hasSchema(model, context)', $storeContent,
            'Should check cache before making API call');
        
        // Should return cached version if available
        $this->assertStringContainsString('return schemas.value[cacheKey]', $storeContent,
            'Should return cached schema when available');
        
        // Should make API call only if not cached
        $hasCheckPos = strpos($storeContent, 'hasSchema(model, context)');
        $apiCallPos = strpos($storeContent, 'axios.get');
        
        $this->assertNotFalse($hasCheckPos, 'Should have cache check');
        $this->assertNotFalse($apiCallPos, 'Should have API call');
        $this->assertLessThan($apiCallPos, $hasCheckPos, 
            'Cache check must come before API call to prevent duplicate requests');
    }

    /**
     * Test that different contexts are treated as separate cache entries
     */
    public function testDifferentContextsAreSeparateCacheEntries(): void
    {
        // This is a logical test based on cache key format
        // If cache key is `${model}:${context}`, then:
        // - "products:list" is different from "products:detail"
        // - "products:list" is different from "products:form"
        // - "products:full" is different from "products:list"
        
        $storeFile = dirname(__DIR__, 2) . '/assets/stores/useCRUD6SchemaStore.ts';
        $storeContent = file_get_contents($storeFile);
        
        // Verify cache key includes both model and context
        $this->assertStringContainsString('${model}:${context', $storeContent,
            'Cache key must include both model and context to separate cache entries');
        
        // This ensures:
        // - products:list and products:detail are separate cache entries
        // - Requesting products:list twice uses cache (no duplicate)
        // - Requesting products:list then products:detail makes 2 API calls (expected)
    }

    /**
     * Test that duplicate prevention still works
     */
    public function testDuplicatePreventionStillWorksWithContexts(): void
    {
        $storeFile = dirname(__DIR__, 2) . '/assets/stores/useCRUD6SchemaStore.ts';
        $storeContent = file_get_contents($storeFile);
        
        // Should still check if already loading
        $this->assertStringContainsString('isLoading(model, context)', $storeContent,
            'Should check if schema is already loading for this model+context');
        
        // Should wait for existing load to complete rather than making duplicate request
        $this->assertStringContainsString('Schema already loading', $storeContent,
            'Should detect when schema is already loading');
        
        // Should use polling to wait for completion
        $this->assertStringContainsString('setInterval', $storeContent,
            'Should poll for load completion to prevent duplicate requests');
    }
}
