<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Tests\Controller;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use UserFrosting\Sprinkle\CRUD6\Controller\Base;
use UserFrosting\Sprinkle\CRUD6\Controller\CreateAction;
use UserFrosting\Sprinkle\CRUD6\Controller\EditAction;
use UserFrosting\Sprinkle\CRUD6\Controller\DeleteAction;

/**
 * Test that controller signatures are compatible with Base class.
 */
class CreateActionSignatureTest extends TestCase
{
    /**
     * Test that CreateAction::__invoke signature is compatible with Base::__invoke.
     */
    public function testCreateActionSignatureIsCompatibleWithBase(): void
    {
        $baseMethod = new ReflectionMethod(Base::class, '__invoke');
        $createMethod = new ReflectionMethod(CreateAction::class, '__invoke');
        
        // Get parameters from both methods
        $baseParams = $baseMethod->getParameters();
        $createParams = $createMethod->getParameters();
        
        // Base has: array $crudSchema, CRUD6ModelInterface $crudModel, ServerRequestInterface $request, ResponseInterface $response
        // CreateAction must have at least: array $crudSchema as first param
        $this->assertGreaterThanOrEqual(1, count($createParams), 'CreateAction::__invoke must have at least 1 parameter');
        
        // Check first parameter is array $crudSchema
        $firstParam = $createParams[0];
        $this->assertSame('crudSchema', $firstParam->getName(), 'First parameter must be named crudSchema');
        
        // Check the type is array
        $type = $firstParam->getType();
        $this->assertNotNull($type, 'First parameter must have a type');
        $this->assertSame('array', $type->getName(), 'First parameter must be of type array');
        
        $this->assertTrue(true, 'CreateAction::__invoke signature is compatible with Base class');
    }
    
    /**
     * Test that EditAction::__invoke signature is compatible with Base::__invoke.
     */
    public function testEditActionSignatureIsCompatibleWithBase(): void
    {
        $editMethod = new ReflectionMethod(EditAction::class, '__invoke');
        $editParams = $editMethod->getParameters();
        
        $this->assertGreaterThanOrEqual(1, count($editParams), 'EditAction::__invoke must have at least 1 parameter');
        
        $firstParam = $editParams[0];
        $this->assertSame('crudSchema', $firstParam->getName(), 'First parameter must be named crudSchema');
        
        $type = $firstParam->getType();
        $this->assertNotNull($type, 'First parameter must have a type');
        $this->assertSame('array', $type->getName(), 'First parameter must be of type array');
    }
    
    /**
     * Test that DeleteAction::__invoke signature is compatible with Base::__invoke.
     */
    public function testDeleteActionSignatureIsCompatibleWithBase(): void
    {
        $deleteMethod = new ReflectionMethod(DeleteAction::class, '__invoke');
        $deleteParams = $deleteMethod->getParameters();
        
        $this->assertGreaterThanOrEqual(1, count($deleteParams), 'DeleteAction::__invoke must have at least 1 parameter');
        
        $firstParam = $deleteParams[0];
        $this->assertSame('crudSchema', $firstParam->getName(), 'First parameter must be named crudSchema');
        
        $type = $firstParam->getType();
        $this->assertNotNull($type, 'First parameter must have a type');
        $this->assertSame('array', $type->getName(), 'First parameter must be of type array');
    }
    
    /**
     * Test that all controller __invoke methods have crudSchema as first parameter.
     */
    public function testAllControllersHaveConsistentSignatures(): void
    {
        $controllers = [
            CreateAction::class,
            EditAction::class,
            DeleteAction::class,
        ];
        
        foreach ($controllers as $controllerClass) {
            $method = new ReflectionMethod($controllerClass, '__invoke');
            $params = $method->getParameters();
            
            $this->assertGreaterThanOrEqual(1, count($params), 
                "{$controllerClass}::__invoke must have at least 1 parameter");
            
            $firstParam = $params[0];
            $this->assertSame('crudSchema', $firstParam->getName(), 
                "First parameter of {$controllerClass}::__invoke must be named crudSchema");
            
            $type = $firstParam->getType();
            $this->assertNotNull($type, 
                "First parameter of {$controllerClass}::__invoke must have a type");
            $this->assertSame('array', $type->getName(), 
                "First parameter of {$controllerClass}::__invoke must be of type array");
        }
    }
}
