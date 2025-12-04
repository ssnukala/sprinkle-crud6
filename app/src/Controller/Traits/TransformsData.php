<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Controller\Traits;

use UserFrosting\Fortress\RequestSchema;
use UserFrosting\Fortress\RequestSchema\RequestSchemaInterface;
use UserFrosting\Sprinkle\Core\Exceptions\ValidationException;

/**
 * Provides common data transformation and validation functionality for CRUD6 controllers.
 * 
 * This trait consolidates the repeated pattern of:
 * 1. Building a request schema from CRUD6 schema configuration
 * 2. Transforming request data using RequestDataTransformer
 * 3. Validating the transformed data using ServerSideValidator
 * 
 * Controllers using this trait must have the following protected properties:
 * - $transformer: RequestDataTransformer - For transforming/whitelisting request data
 * - $validator: ServerSideValidator - For validating data against schema rules
 * - $logger: DebugLoggerInterface - For error logging
 * 
 * And must implement:
 * - debugLog(string $message, array $context = []): void - For debug logging
 * - getValidationRules(string|array $modelNameOrSchema): array - To extract validation rules from schema
 * 
 * @see \UserFrosting\Sprinkle\CRUD6\Controller\CreateAction
 * @see \UserFrosting\Sprinkle\CRUD6\Controller\EditAction
 * @see \UserFrosting\Sprinkle\CRUD6\Controller\UpdateFieldAction
 */
trait TransformsData
{
    /**
     * Transform and validate request data in one step.
     * 
     * Combines the common pattern of:
     * 1. Building request schema from CRUD6 schema
     * 2. Transforming request params using the schema
     * 3. Validating the transformed data
     * 
     * @param array $schema The CRUD6 schema configuration
     * @param array $params The raw request parameters
     * 
     * @return array The transformed and validated data
     * 
     * @throws ValidationException If validation fails
     */
    protected function transformAndValidate(array $schema, array $params): array
    {
        $requestSchema = $this->buildRequestSchema($schema);
        $data = $this->transformRequestData($requestSchema, $params, $schema);
        $this->validateRequestData($requestSchema, $data, $schema);
        
        return $data;
    }

    /**
     * Build a RequestSchema from CRUD6 schema configuration.
     * 
     * @param array $schema The CRUD6 schema configuration
     * 
     * @return RequestSchemaInterface The built request schema
     */
    protected function buildRequestSchema(array $schema): RequestSchemaInterface
    {
        $validationRules = $this->getValidationRules($schema);
        return new RequestSchema($validationRules);
    }

    /**
     * Transform request parameters using the request schema.
     * 
     * Uses the RequestDataTransformer to whitelist and transform parameters
     * according to the schema definition.
     * 
     * @param RequestSchemaInterface $requestSchema The request schema
     * @param array                  $params        The raw request parameters
     * @param array                  $crudSchema    The CRUD6 schema (for logging context)
     * 
     * @return array The transformed data
     */
    protected function transformRequestData(RequestSchemaInterface $requestSchema, array $params, array $crudSchema): array
    {
        $data = $this->transformer->transform($requestSchema, $params);
        
        $this->debugLog("CRUD6 [TransformsData] Data transformed", [
            'model' => $crudSchema['model'] ?? 'unknown',
            'input_count' => count($params),
            'output_count' => count($data),
        ]);
        
        return $data;
    }

    /**
     * Validate request data against the schema.
     * 
     * @param RequestSchemaInterface $requestSchema The request schema with validation rules
     * @param array                  $data          The data to validate
     * @param array                  $crudSchema    The CRUD6 schema (for logging context)
     * 
     * @return void
     * 
     * @throws ValidationException If validation fails
     */
    protected function validateRequestData(RequestSchemaInterface $requestSchema, array $data, array $crudSchema): void
    {
        $this->debugLog("CRUD6 [TransformsData] Starting validation", [
            'model' => $crudSchema['model'] ?? 'unknown',
            'data_keys' => array_keys($data),
        ]);

        $errors = $this->validator->validate($requestSchema, $data);
        
        if (count($errors) !== 0) {
            $this->logger->error("Line:125 CRUD6 [TransformsData] Validation failed", [
                'model' => $crudSchema['model'] ?? 'unknown',
                'errors' => $errors,
                'error_count' => count($errors),
            ]);

            $e = new ValidationException();
            $e->addErrors($errors);

            throw $e;
        }

        $this->debugLog("CRUD6 [TransformsData] Validation successful", [
            'model' => $crudSchema['model'] ?? 'unknown',
        ]);
    }

    /**
     * Transform and validate a single field.
     * 
     * Used by UpdateFieldAction for partial updates where only one field
     * needs to be validated.
     * 
     * @param string $fieldName   The field name
     * @param array  $fieldConfig The field configuration from schema
     * @param array  $params      The request parameters
     * @param array  $crudSchema  The CRUD6 schema (for logging context)
     * 
     * @return array The transformed data with the validated field
     * 
     * @throws ValidationException If validation fails
     */
    protected function transformAndValidateField(string $fieldName, array $fieldConfig, array $params, array $crudSchema): array
    {
        $validationRules = $fieldConfig['validation'] ?? [];
        
        // Create a validation schema for just this field
        $requestSchema = new RequestSchema([
            $fieldName => $validationRules
        ]);

        // Validate the single field
        $errors = $this->validator->validate($requestSchema, $params);
        
        if (count($errors) !== 0) {
            $this->logger->error("Line:170 CRUD6 [TransformsData] Field validation failed", [
                'model' => $crudSchema['model'] ?? 'unknown',
                'field' => $fieldName,
                'errors' => $errors,
            ]);

            $e = new ValidationException();
            $e->addErrors($errors);

            throw $e;
        }

        // Transform data using injected transformer
        $data = $this->transformer->transform($requestSchema, $params);
        
        // For fields with no validation rules (especially booleans), ensure the field is in the data
        // RequestDataTransformer may skip fields with empty validation schemas
        $fieldType = $fieldConfig['type'] ?? 'string';
        if (!array_key_exists($fieldName, $data) && array_key_exists($fieldName, $params)) {
            $data[$fieldName] = $params[$fieldName];
            $this->debugLog("CRUD6 [TransformsData] Field added to data (no validation rules)", [
                'model' => $crudSchema['model'] ?? 'unknown',
                'field' => $fieldName,
                'type' => $fieldType,
            ]);
        }

        $this->debugLog("CRUD6 [TransformsData] Field validation passed", [
            'model' => $crudSchema['model'] ?? 'unknown',
            'field' => $fieldName,
        ]);

        return $data;
    }
}
