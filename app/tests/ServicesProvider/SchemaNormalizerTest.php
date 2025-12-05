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
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaNormalizer;

/**
 * Schema Normalizer Test.
 *
 * Tests the SchemaNormalizer class functionality.
 */
class SchemaNormalizerTest extends TestCase
{
    /**
     * Test normalizeBooleanTypes handles all boolean variants correctly.
     */
    public function testNormalizeBooleanTypesHandlesAllVariants(): void
    {
        $normalizer = new SchemaNormalizer();

        // Test schema with all boolean type variants
        $schema = [
            'model' => 'test_model',
            'table' => 'test_table',
            'fields' => [
                'flag_enabled' => [
                    'type' => 'boolean-tgl',
                    'label' => 'Enabled'
                ],
                'is_verified' => [
                    'type' => 'boolean-chk',
                    'label' => 'Verified'
                ],
                'accepts_marketing' => [
                    'type' => 'boolean-yn',
                    'label' => 'Accepts Marketing'
                ],
                'show_in_list' => [
                    'type' => 'boolean-sel',
                    'label' => 'Show in List'
                ],
                'is_admin' => [
                    'type' => 'boolean',
                    'label' => 'Is Admin'
                ],
            ]
        ];

        $result = $normalizer->normalizeBooleanTypes($schema);

        // All boolean variants should be normalized to 'boolean' type
        $this->assertEquals('boolean', $result['fields']['flag_enabled']['type']);
        $this->assertEquals('boolean', $result['fields']['is_verified']['type']);
        $this->assertEquals('boolean', $result['fields']['accepts_marketing']['type']);
        $this->assertEquals('boolean', $result['fields']['show_in_list']['type']);
        $this->assertEquals('boolean', $result['fields']['is_admin']['type']);

        // Check that UI types are set correctly
        $this->assertEquals('toggle', $result['fields']['flag_enabled']['ui']);
        $this->assertEquals('checkbox', $result['fields']['is_verified']['ui']);
        $this->assertEquals('select', $result['fields']['accepts_marketing']['ui']);
        $this->assertEquals('select', $result['fields']['show_in_list']['ui']);
        $this->assertEquals('checkbox', $result['fields']['is_admin']['ui']);
    }

    /**
     * Test normalizeBooleanTypes preserves explicit UI configuration.
     */
    public function testNormalizeBooleanTypesPreservesExplicitUI(): void
    {
        $normalizer = new SchemaNormalizer();

        // Test schema with explicit UI configuration
        $schema = [
            'model' => 'test_model',
            'table' => 'test_table',
            'fields' => [
                'flag_enabled' => [
                    'type' => 'boolean-tgl',
                    'label' => 'Enabled',
                    'ui' => 'custom'
                ],
            ]
        ];

        $result = $normalizer->normalizeBooleanTypes($schema);

        // UI should be preserved when explicitly set
        $this->assertEquals('boolean', $result['fields']['flag_enabled']['type']);
        $this->assertEquals('custom', $result['fields']['flag_enabled']['ui']);
    }

    /**
     * Test normalizeBooleanTypes handles empty or missing fields.
     */
    public function testNormalizeBooleanTypesHandlesEmptyFields(): void
    {
        $normalizer = new SchemaNormalizer();

        // Test schema without fields
        $schema = [
            'model' => 'test_model',
            'table' => 'test_table',
        ];

        $result = $normalizer->normalizeBooleanTypes($schema);

        // Should not throw an exception
        $this->assertEquals('test_model', $result['model']);
        $this->assertEquals('test_table', $result['table']);
    }

    /**
     * Test normalizeVisibilityFlags creates show_in array.
     */
    public function testNormalizeVisibilityFlagsCreatesShowInArray(): void
    {
        $normalizer = new SchemaNormalizer();

        $schema = [
            'model' => 'test',
            'fields' => [
                'name' => [
                    'type' => 'string',
                    'listable' => true,
                    'editable' => true,
                    'viewable' => true,
                ],
                'password' => [
                    'type' => 'password',
                    'editable' => true,
                ],
            ]
        ];

        $result = $normalizer->normalizeVisibilityFlags($schema);

        // Regular field should have all contexts
        $this->assertContains('list', $result['fields']['name']['show_in']);
        $this->assertContains('create', $result['fields']['name']['show_in']);
        $this->assertContains('edit', $result['fields']['name']['show_in']);
        $this->assertContains('detail', $result['fields']['name']['show_in']);

        // Password field should not have 'detail'
        $this->assertContains('create', $result['fields']['password']['show_in']);
        $this->assertContains('edit', $result['fields']['password']['show_in']);
        $this->assertNotContains('detail', $result['fields']['password']['show_in']);
    }

    /**
     * Test normalize applies all normalizations.
     */
    public function testNormalizeAppliesAllNormalizations(): void
    {
        $normalizer = new SchemaNormalizer();

        $schema = [
            'model' => 'test',
            'fields' => [
                'enabled' => [
                    'type' => 'boolean-tgl',
                ],
            ]
        ];

        $result = $normalizer->normalize($schema);

        // Should have normalized boolean type
        $this->assertEquals('boolean', $result['fields']['enabled']['type']);
        $this->assertEquals('toggle', $result['fields']['enabled']['ui']);

        // Should have visibility flags
        $this->assertArrayHasKey('show_in', $result['fields']['enabled']);
    }
}
