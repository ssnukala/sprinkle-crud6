<?php

declare(strict_types=1);

namespace UserFrosting\Sprinkle\CRUD6\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use UserFrosting\Sprinkle\Account\Authenticate\Authenticator;
use UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager;
use UserFrosting\Sprinkle\Core\Log\DebugLoggerInterface;
use UserFrosting\Sprinkle\CRUD6\ServicesProvider\SchemaService;

class ModelController extends Controller
{
    public function __construct(
        protected AuthorizationManager $authorizer,
        protected Authenticator $authenticator,
        protected DebugLoggerInterface $logger,
        protected SchemaService $schemaService
    ) {
        parent::__construct($authorizer, $authenticator, $logger);
    }

    public function listRecords(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $model = $this->getModel($request);
        $schema = $this->getSchema($request);
        $this->validateAccess($schema, 'read');
        $this->logger->debug("CRUD6: Listing records using model for: {$model}");
        try {
            $modelInstance = $this->schemaService->getModelInstance($model);
            $query = $modelInstance->newQuery();
            $queryParams = $request->getQueryParams();
            $this->applyFilters($query, $schema, $queryParams);
            $this->applySorting($query, $schema, $queryParams);
            $page = (int) ($queryParams['page'] ?? 1);
            $perPage = min((int) ($queryParams['size'] ?? 10), 100);
            $offset = ($page - 1) * $perPage;
            $total = $query->count();
            $records = $query->skip($offset)->take($perPage)->get();
            $responseData = [
                'model' => $model,
                'page' => $page,
                'size' => $perPage,
                'total' => $total,
                'pages' => ceil($total / $perPage),
                'data' => $records->toArray()
            ];
            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $this->logger->error("CRUD6: Failed to list records for model: {$model}", [
                'error' => $e->getMessage()
            ]);
            $errorData = [
                'error' => 'Failed to retrieve records',
                'message' => $e->getMessage()
            ];
            $response->getBody()->write(json_encode($errorData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function createRecord(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $model = $this->getModel($request);
        $schema = $this->getSchema($request);
        $this->validateAccess($schema, 'create');
        $this->logger->debug("CRUD6: Creating record using model for: {$model}");
        try {
            $modelInstance = $this->schemaService->getModelInstance($model);
            $data = $request->getParsedBody();
            $fillableData = array_intersect_key($data, array_flip($modelInstance->getFillable()));
            $newRecord = $modelInstance->create($fillableData);
            $this->logger->debug("CRUD6: Created record with ID: {$newRecord->id} for model: {$model}");
            $responseData = [
                'message' => "Record created successfully",
                'model' => $model,
                'id' => $newRecord->id,
                'data' => $newRecord->toArray()
            ];
            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $this->logger->error("CRUD6: Failed to create record for model: {$model}", [
                'error' => $e->getMessage(),
                'data' => $request->getParsedBody()
            ]);
            $errorData = [
                'error' => 'Failed to create record',
                'message' => $e->getMessage()
            ];
            $response->getBody()->write(json_encode($errorData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function readRecord(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $model = $this->getModel($request);
        $schema = $this->getSchema($request);
        $recordId = $this->getRecordId($request);
        $this->validateAccess($schema, 'read');
        $this->logger->debug("CRUD6: Reading record ID: {$recordId} using model for: {$model}");
        try {
            $modelInstance = $this->schemaService->getModelInstance($model);
            $record = $modelInstance->find($recordId);
            if (!$record) {
                $errorData = [
                    'error' => 'Record not found',
                    'model' => $model,
                    'id' => $recordId
                ];
                $response->getBody()->write(json_encode($errorData));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
            $responseData = [
                'model' => $model,
                'id' => $recordId,
                'data' => $record->toArray()
            ];
            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $this->logger->error("CRUD6: Failed to read record for model: {$model}", [
                'error' => $e->getMessage(),
                'id' => $recordId
            ]);
            $errorData = [
                'error' => 'Failed to read record',
                'message' => $e->getMessage()
            ];
            $response->getBody()->write(json_encode($errorData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    protected function applyFilters($query, array $schema, array $queryParams): void
    {
        $filterableFields = $this->getFilterableFields($schema);
        foreach ($filterableFields as $field) {
            if (isset($queryParams[$field]) && $queryParams[$field] !== '') {
                $fieldConfig = $schema['fields'][$field] ?? [];
                $filterType = $fieldConfig['filter_type'] ?? 'equals';
                $value = $queryParams[$field];
                switch ($filterType) {
                    case 'like':
                        $query->where($field, 'LIKE', "%{$value}%");
                        break;
                    case 'starts_with':
                        $query->where($field, 'LIKE', "{$value}%");
                        break;
                    case 'ends_with':
                        $query->where($field, 'LIKE', "%{$value}");
                        break;
                    case 'greater_than':
                        $query->where($field, '>', $value);
                        break;
                    case 'less_than':
                        $query->where($field, '<', $value);
                        break;
                    case 'between':
                        if (is_array($value) && count($value) === 2) {
                            $query->whereBetween($field, $value);
                        }
                        break;
                    default:
                        $query->where($field, $value);
                        break;
                }
            }
        }
    }

    protected function applySorting($query, array $schema, array $queryParams): void
    {
        $sortableFields = $this->getSortableFields($schema);
        if (isset($queryParams['sort']) && in_array($queryParams['sort'], $sortableFields)) {
            $direction = ($queryParams['order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
            $query->orderBy($queryParams['sort'], $direction);
        } elseif (isset($schema['default_sort'])) {
            foreach ($schema['default_sort'] as $field => $direction) {
                if (in_array($field, $sortableFields)) {
                    $query->orderBy($field, $direction);
                }
            }
        }
    }
}
