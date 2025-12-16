<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Tests;

use PHPUnit\Framework\TestCase;
use UserFrosting\Sprinkle\Core\Sprinkle\Recipe\MigrationRecipe;
use UserFrosting\Sprinkle\Core\Sprinkle\Recipe\SeedRecipe;
use UserFrosting\Sprinkle\SprinkleRecipe;
use UserFrosting\Sprinkle\CRUD6\CRUD6;

/**
 * Test CRUD6 sprinkle configuration.
 *
 * Verifies that the CRUD6 sprinkle properly implements required interfaces
 * and returns the correct migrations and seeds.
 */
class CRUD6Test extends TestCase
{
    /**
     * Test that CRUD6 implements SprinkleRecipe interface.
     */
    public function testImplementsSprinkleRecipe(): void
    {
        $sprinkle = new CRUD6();
        $this->assertInstanceOf(SprinkleRecipe::class, $sprinkle);
    }

    /**
     * Test that CRUD6 implements MigrationRecipe interface.
     */
    public function testImplementsMigrationRecipe(): void
    {
        $sprinkle = new CRUD6();
        $this->assertInstanceOf(MigrationRecipe::class, $sprinkle);
    }

    /**
     * Test that CRUD6 implements SeedRecipe interface.
     */
    public function testImplementsSeedRecipe(): void
    {
        $sprinkle = new CRUD6();
        $this->assertInstanceOf(SeedRecipe::class, $sprinkle);
    }

    /**
     * Test that getMigrations returns empty array.
     * 
     * CRUD6 sprinkle does not define any migrations - it works with existing tables
     * defined by JSON schemas. This is intentional design as CRUD6 is schema-driven
     * and doesn't require its own database migrations.
     */
    public function testGetMigrationsReturnsEmptyArray(): void
    {
        $sprinkle = new CRUD6();
        $migrations = $sprinkle->getMigrations();

        // Assert that migrations is an empty array (CRUD6 has no migrations by design)
        $this->assertIsArray($migrations);
        $this->assertEmpty($migrations);
    }

    /**
     * Test that getName returns expected value.
     */
    public function testGetName(): void
    {
        $sprinkle = new CRUD6();
        $this->assertEquals('CRUD6 Sprinkle', $sprinkle->getName());
    }

    /**
     * Test that getPath returns a valid directory.
     */
    public function testGetPath(): void
    {
        $sprinkle = new CRUD6();
        $path = $sprinkle->getPath();
        
        $this->assertIsString($path);
        $this->assertDirectoryExists($path);
    }
}
