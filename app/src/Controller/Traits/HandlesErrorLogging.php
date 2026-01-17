<?php

declare(strict_types=1);

/*
 * UserFrosting CRUD6 Sprinkle (http://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-crud6
 * @copyright Copyright (c) 2026 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-crud6/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\CRUD6\Controller\Traits;

/**
 * Trait for standardized error logging in CRUD6 controllers.
 * 
 * Provides common error logging patterns for consistent error reporting
 * across all CRUD6 controllers. Uses the controller's logger instance
 * to record structured error information.
 */
trait HandlesErrorLogging
{
    /**
     * Log an error with standardized format for failed operations.
     * 
     * Creates a consistent error log entry with operation context,
     * exception details, and optional additional context.
     * 
     * @param string     $operation   The operation name (e.g., 'CREATE', 'DELETE', 'UPDATE_FIELD')
     * @param \Exception $exception   The caught exception
     * @param array      $context     Additional context (model, record_id, etc.)
     * 
     * @return void
     */
    protected function logOperationError(string $operation, \Exception $exception, array $context = []): void
    {
        $errorContext = array_merge($context, [
            'error_type' => get_class($exception),
            'error_message' => $exception->getMessage(),
            'error_file' => $exception->getFile(),
            'error_line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $this->logger->error("Line:38 CRUD6 [{$operation}] ===== {$operation} REQUEST FAILED =====", $errorContext);
    }

    /**
     * Log a warning for invalid configuration or data.
     * 
     * @param string $operation The operation name
     * @param string $message   The warning message
     * @param array  $context   Additional context
     * 
     * @return void
     */
    protected function logWarning(string $operation, string $message, array $context = []): void
    {
        $this->logger->warning("Line:52 CRUD6 [{$operation}] {$message}", $context);
    }

    /**
     * Log validation failure details.
     * 
     * @param string $operation The operation name
     * @param array  $errors    Validation errors
     * @param array  $context   Additional context (model, field, etc.)
     * 
     * @return void
     */
    protected function logValidationError(string $operation, array $errors, array $context = []): void
    {
        $errorContext = array_merge($context, [
            'validation_errors' => $errors,
        ]);

        $this->logger->error("Line:70 CRUD6 [{$operation}] Validation failed", $errorContext);
    }
}
