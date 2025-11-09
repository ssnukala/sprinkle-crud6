# CRUD Operations in RegSevak

## Overview

RegSevak implements comprehensive CRUD (Create, Read, Update, Delete) operations for managing registrations and related data. This document details the implementation patterns, API endpoints, and common workflows used throughout the application.

## CRUD Architecture

### RESTful API Design

RegSevak follows REST principles for CRUD operations:

```
POST   /api/registrations          - Create new registration
GET    /api/registrations          - List all registrations (paginated)
GET    /api/registrations/{id}     - Read single registration
PUT    /api/registrations/{id}     - Update registration (full)
PATCH  /api/registrations/{id}     - Update registration (partial)
DELETE /api/registrations/{id}     - Delete registration
```

### Controller Structure

Each CRUD operation is typically implemented as a separate action class:

```
src/Controller/
├── Registration/
│   ├── CreateAction.php
│   ├── ListAction.php
│   ├── ViewAction.php
│   ├── UpdateAction.php
│   └── DeleteAction.php
```

## Create Operation

### Create Flow

```
User fills form → Validates data → Creates record → Returns response
```

### CreateAction Controller

```php
namespace UserFrosting\Sprinkle\RegSevak\Controller\Registration;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use UserFrosting\Sprinkle\RegSevak\Database\Models\Registration;

class CreateAction
{
    protected $ci;
    
    public function __construct($ci)
    {
        $this->ci = $ci;
    }
    
    public function __invoke(Request $request, Response $response, array $args)
    {
        // Get POST data
        $params = $request->getParsedBody();
        
        // Load request schema for validation
        $schema = $this->ci->schema->load('requests/registration/create.yaml');
        
        // Whitelist and set parameter defaults
        $transformer = new RequestDataTransformer($schema);
        $data = $transformer->transform($params);
        
        // Validate data
        $validator = new ServerSideValidator($schema, $this->ci->translator);
        if (!$validator->validate($data)) {
            $ms = $this->ci->alerts;
            $ms->addValidationErrors($validator);
            
            return $response->withJson([], 400);
        }
        
        // Check permissions
        $currentUser = $this->ci->currentUser;
        if (!$this->ci->authorizer->checkAccess($currentUser, 'create_registration')) {
            throw new ForbiddenException();
        }
        
        // Create the registration
        $registration = new Registration($data);
        $registration->user_id = $currentUser->id;
        $registration->status = 'pending';
        $registration->save();
        
        // Success message
        $ms = $this->ci->alerts;
        $ms->addMessageTranslated('success', 'REGISTRATION.CREATED');
        
        // Return created registration
        return $response->withJson($registration, 201);
    }
}
```

### Validation Schema

```yaml
# schema/requests/registration/create.yaml
name:
  validators:
    required:
      message: "Name is required"
    length:
      min: 3
      max: 100
      message: "Name must be between 3 and 100 characters"

email:
  validators:
    required:
      message: "Email is required"
    email:
      message: "Please provide a valid email address"
    unique:
      model: UserFrosting\Sprinkle\RegSevak\Database\Models\Registration
      field: email
      message: "This email is already registered"

phone:
  validators:
    required:
      message: "Phone number is required"
    telephone:
      message: "Please provide a valid phone number"

address:
  validators:
    length:
      max: 255
      message: "Address must be less than 255 characters"
```

### Frontend Form

```twig
{# templates/modals/registration/create.html.twig #}
<div class="modal" id="registration-create-modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">New Registration</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="registration-create-form" method="POST" action="{{ site.uri.public }}/api/registrations">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="name">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control" id="phone" name="phone" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i> Submit Registration
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
```

### Frontend JavaScript

```javascript
// assets/js/pages/registrations.js

$(document).ready(function() {
    // Initialize form modal
    $('#registration-create-modal').ufModal({
        validators: page.validators.create
    });
    
    // Handle form submission
    $('#registration-create-form').ufForm({
        validators: page.validators.create
    }).on('submitSuccess.ufForm', function(event, data, textStatus, jqXHR) {
        // Close modal
        $('#registration-create-modal').modal('hide');
        
        // Refresh table
        $('#registrations-table').DataTable().ajax.reload();
        
        // Show success message
        showAlert('Registration submitted successfully', 'success');
    });
});
```

## Read Operation

### List Action (Read Multiple)

```php
namespace UserFrosting\Sprinkle\RegSevak\Controller\Registration;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use UserFrosting\Sprinkle\RegSevak\Sprunje\RegistrationSprunje;

class ListAction
{
    protected $ci;
    
    public function __construct($ci)
    {
        $this->ci = $ci;
    }
    
    public function __invoke(Request $request, Response $response, array $args)
    {
        // Check permissions
        $currentUser = $this->ci->currentUser;
        if (!$this->ci->authorizer->checkAccess($currentUser, 'uri_registrations')) {
            throw new ForbiddenException();
        }
        
        // Get query parameters
        $params = $request->getQueryParams();
        
        // Create and execute Sprunje
        $sprunje = new RegistrationSprunje($this->ci, $params);
        
        // Return JSON response
        return $sprunje->toResponse($response);
    }
}
```

### View Action (Read Single)

```php
namespace UserFrosting\Sprinkle\RegSevak\Controller\Registration;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use UserFrosting\Sprinkle\RegSevak\Database\Models\Registration;

class ViewAction
{
    protected $ci;
    
    public function __construct($ci)
    {
        $this->ci = $ci;
    }
    
    public function __invoke(Request $request, Response $response, array $args)
    {
        // Get registration ID
        $id = $args['id'];
        
        // Load registration with relationships
        $registration = Registration::with(['user', 'documents'])
            ->find($id);
        
        if (!$registration) {
            throw new NotFoundException();
        }
        
        // Check permissions
        $currentUser = $this->ci->currentUser;
        
        // Users can only view their own registrations unless admin
        if (!$currentUser->hasRole('admin') && $registration->user_id != $currentUser->id) {
            throw new ForbiddenException();
        }
        
        // Return registration data
        return $response->withJson($registration, 200);
    }
}
```

### Frontend Detail View

```twig
{# templates/pages/registration-detail.html.twig #}
{% extends "pages/abstract/base.html.twig" %}

{% block page_title %}Registration Details{% endblock %}

{% block body_content %}
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3>Registration #{{ registration.id }}</h3>
                        <div class="card-header-actions">
                            {% if checkAccess('update_registration') or 
                                 (checkAccess('update_own_registration') and registration.user_id == current_user.id) %}
                                <button class="btn btn-primary btn-edit" data-id="{{ registration.id }}">
                                    <i class="fa fa-edit"></i> Edit
                                </button>
                            {% endif %}
                            
                            {% if checkAccess('delete_registration') %}
                                <button class="btn btn-danger btn-delete" data-id="{{ registration.id }}">
                                    <i class="fa fa-trash"></i> Delete
                                </button>
                            {% endif %}
                        </div>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-3">Name:</dt>
                            <dd class="col-sm-9">{{ registration.name }}</dd>
                            
                            <dt class="col-sm-3">Email:</dt>
                            <dd class="col-sm-9">{{ registration.email }}</dd>
                            
                            <dt class="col-sm-3">Phone:</dt>
                            <dd class="col-sm-9">{{ registration.phone }}</dd>
                            
                            <dt class="col-sm-3">Address:</dt>
                            <dd class="col-sm-9">{{ registration.address }}</dd>
                            
                            <dt class="col-sm-3">Status:</dt>
                            <dd class="col-sm-9">
                                <span class="badge badge-{{ registration.status == 'approved' ? 'success' : (registration.status == 'rejected' ? 'danger' : 'warning') }}">
                                    {{ registration.status|upper }}
                                </span>
                            </dd>
                            
                            <dt class="col-sm-3">Submitted:</dt>
                            <dd class="col-sm-9">{{ registration.created_at|date('Y-m-d H:i:s') }}</dd>
                            
                            {% if registration.updated_at %}
                                <dt class="col-sm-3">Last Updated:</dt>
                                <dd class="col-sm-9">{{ registration.updated_at|date('Y-m-d H:i:s') }}</dd>
                            {% endif %}
                        </dl>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                {# Documents, notes, history, etc. #}
                <div class="card">
                    <div class="card-header">
                        <h4>Attached Documents</h4>
                    </div>
                    <div class="card-body">
                        {% if registration.documents|length > 0 %}
                            <ul class="list-unstyled">
                                {% for document in registration.documents %}
                                    <li>
                                        <a href="{{ site.uri.public }}/documents/{{ document.id }}/download">
                                            <i class="fa fa-file"></i> {{ document.filename }}
                                        </a>
                                    </li>
                                {% endfor %}
                            </ul>
                        {% else %}
                            <p class="text-muted">No documents attached</p>
                        {% endif %}
                    </div>
                </div>
            </div>
        </div>
    </div>
{% endblock %}
```

## Update Operation

### UpdateAction Controller

```php
namespace UserFrosting\Sprinkle\RegSevak\Controller\Registration;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use UserFrosting\Sprinkle\RegSevak\Database\Models\Registration;

class UpdateAction
{
    protected $ci;
    
    public function __construct($ci)
    {
        $this->ci = $ci;
    }
    
    public function __invoke(Request $request, Response $response, array $args)
    {
        // Get registration ID
        $id = $args['id'];
        
        // Load registration
        $registration = Registration::find($id);
        
        if (!$registration) {
            throw new NotFoundException();
        }
        
        // Check permissions
        $currentUser = $this->ci->currentUser;
        
        // Determine permission based on ownership
        if ($registration->user_id == $currentUser->id) {
            $permission = 'update_own_registration';
        } else {
            $permission = 'update_registration';
        }
        
        if (!$this->ci->authorizer->checkAccess($currentUser, $permission)) {
            throw new ForbiddenException();
        }
        
        // Get PUT/PATCH data
        $params = $request->getParsedBody();
        
        // Load validation schema
        $schema = $this->ci->schema->load('requests/registration/update.yaml');
        
        // Transform and validate
        $transformer = new RequestDataTransformer($schema);
        $data = $transformer->transform($params);
        
        $validator = new ServerSideValidator($schema, $this->ci->translator);
        if (!$validator->validate($data)) {
            $ms = $this->ci->alerts;
            $ms->addValidationErrors($validator);
            
            return $response->withJson([], 400);
        }
        
        // Update fields
        foreach ($data as $field => $value) {
            if ($field !== 'id' && $field !== 'user_id') {
                $registration->$field = $value;
            }
        }
        
        // Save changes
        $registration->save();
        
        // Log the update
        $this->ci->userActivityLogger->info("User {$currentUser->user_name} updated registration {$registration->id}.");
        
        // Success message
        $ms = $this->ci->alerts;
        $ms->addMessageTranslated('success', 'REGISTRATION.UPDATED');
        
        // Return updated registration
        return $response->withJson($registration, 200);
    }
}
```

### Partial Update (PATCH)

```php
namespace UserFrosting\Sprinkle\RegSevak\Controller\Registration;

class PartialUpdateAction
{
    protected $ci;
    
    public function __construct($ci)
    {
        $this->ci = $ci;
    }
    
    public function __invoke(Request $request, Response $response, array $args)
    {
        // Get registration ID and field name
        $id = $args['id'];
        $field = $args['field'];
        
        // Load registration
        $registration = Registration::find($id);
        
        if (!$registration) {
            throw new NotFoundException();
        }
        
        // Check permissions
        $currentUser = $this->ci->currentUser;
        if (!$this->ci->authorizer->checkAccess($currentUser, 'update_registration_field')) {
            throw new ForbiddenException();
        }
        
        // Get new value
        $params = $request->getParsedBody();
        $value = $params['value'] ?? null;
        
        // Validate field exists and is updatable
        $allowedFields = ['status', 'notes', 'priority'];
        if (!in_array($field, $allowedFields)) {
            throw new BadRequestException("Field '{$field}' cannot be updated");
        }
        
        // Validate value
        $this->validateFieldValue($field, $value);
        
        // Update field
        $registration->$field = $value;
        $registration->save();
        
        // Log the update
        $this->ci->userActivityLogger->info("User {$currentUser->user_name} updated {$field} for registration {$registration->id}.");
        
        // Return success
        return $response->withJson([
            'field' => $field,
            'value' => $value,
            'message' => 'Field updated successfully'
        ], 200);
    }
    
    protected function validateFieldValue($field, $value)
    {
        // Field-specific validation
        switch ($field) {
            case 'status':
                $validStatuses = ['pending', 'approved', 'rejected', 'in_review'];
                if (!in_array($value, $validStatuses)) {
                    throw new BadRequestException("Invalid status value");
                }
                break;
            case 'priority':
                if (!is_numeric($value) || $value < 0 || $value > 10) {
                    throw new BadRequestException("Priority must be between 0 and 10");
                }
                break;
        }
    }
}
```

### Frontend Edit Form

```javascript
// Edit registration
function editRegistration(id) {
    // Load registration data
    $.ajax({
        url: site.uri.public + '/api/registrations/' + id,
        method: 'GET',
        success: function(registration) {
            // Populate edit form
            $('#edit-registration-id').val(registration.id);
            $('#edit-name').val(registration.name);
            $('#edit-email').val(registration.email);
            $('#edit-phone').val(registration.phone);
            $('#edit-address').val(registration.address);
            
            // Show modal
            $('#registration-edit-modal').modal('show');
        }
    });
}

// Handle edit form submission
$('#registration-edit-form').submit(function(e) {
    e.preventDefault();
    
    var id = $('#edit-registration-id').val();
    var data = {
        name: $('#edit-name').val(),
        email: $('#edit-email').val(),
        phone: $('#edit-phone').val(),
        address: $('#edit-address').val()
    };
    
    $.ajax({
        url: site.uri.public + '/api/registrations/' + id,
        method: 'PUT',
        data: data,
        success: function(response) {
            // Close modal
            $('#registration-edit-modal').modal('hide');
            
            // Refresh table
            $('#registrations-table').DataTable().ajax.reload();
            
            // Show success message
            showAlert('Registration updated successfully', 'success');
        },
        error: function(xhr) {
            showAlert('Failed to update registration', 'error');
        }
    });
});

// Inline field update
$('.update-status').change(function() {
    var id = $(this).data('id');
    var newStatus = $(this).val();
    
    $.ajax({
        url: site.uri.public + '/api/registrations/' + id + '/status',
        method: 'PATCH',
        data: { value: newStatus },
        success: function() {
            showAlert('Status updated', 'success');
        }
    });
});
```

## Delete Operation

### DeleteAction Controller

```php
namespace UserFrosting\Sprinkle\RegSevak\Controller\Registration;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use UserFrosting\Sprinkle\RegSevak\Database\Models\Registration;

class DeleteAction
{
    protected $ci;
    
    public function __construct($ci)
    {
        $this->ci = $ci;
    }
    
    public function __invoke(Request $request, Response $response, array $args)
    {
        // Get registration ID
        $id = $args['id'];
        
        // Load registration
        $registration = Registration::find($id);
        
        if (!$registration) {
            throw new NotFoundException();
        }
        
        // Check permissions
        $currentUser = $this->ci->currentUser;
        if (!$this->ci->authorizer->checkAccess($currentUser, 'delete_registration')) {
            throw new ForbiddenException();
        }
        
        // Check if registration can be deleted
        if ($registration->status == 'approved') {
            throw new BadRequestException('Cannot delete approved registrations');
        }
        
        // Delete related records first (if any)
        $registration->documents()->delete();
        
        // Log the deletion
        $this->ci->userActivityLogger->info("User {$currentUser->user_name} deleted registration {$registration->id}.");
        
        // Delete registration
        $registration->delete();
        
        // Success message
        $ms = $this->ci->alerts;
        $ms->addMessageTranslated('success', 'REGISTRATION.DELETED');
        
        // Return success
        return $response->withJson([
            'message' => 'Registration deleted successfully'
        ], 200);
    }
}
```

### Soft Delete Implementation

```php
namespace UserFrosting\Sprinkle\RegSevak\Database\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use UserFrosting\Sprinkle\Core\Database\Models\Model;

class Registration extends Model
{
    use SoftDeletes;
    
    /**
     * @var array The attributes that should be mutated to dates.
     */
    protected $dates = ['deleted_at'];
    
    // ... rest of model
}
```

### Frontend Delete Confirmation

```javascript
// Delete registration with confirmation
function deleteRegistration(id) {
    // Show confirmation dialog
    if (!confirm('Are you sure you want to delete this registration?')) {
        return;
    }
    
    // Optional: Show a more sophisticated modal
    $('#delete-confirm-modal').data('registration-id', id).modal('show');
}

// Handle confirmed deletion
$('#confirm-delete-btn').click(function() {
    var id = $('#delete-confirm-modal').data('registration-id');
    
    $.ajax({
        url: site.uri.public + '/api/registrations/' + id,
        method: 'DELETE',
        success: function() {
            // Close modal
            $('#delete-confirm-modal').modal('hide');
            
            // Refresh table
            $('#registrations-table').DataTable().ajax.reload();
            
            // Show success message
            showAlert('Registration deleted successfully', 'success');
        },
        error: function(xhr) {
            // Handle error
            var message = xhr.responseJSON?.message || 'Failed to delete registration';
            showAlert(message, 'error');
        }
    });
});
```

## Batch CRUD Operations

### Batch Delete

```php
namespace UserFrosting\Sprinkle\RegSevak\Controller\Registration;

class BatchDeleteAction
{
    protected $ci;
    
    public function __construct($ci)
    {
        $this->ci = $ci;
    }
    
    public function __invoke(Request $request, Response $response, array $args)
    {
        // Get IDs to delete
        $params = $request->getParsedBody();
        $ids = $params['ids'] ?? [];
        
        if (empty($ids)) {
            throw new BadRequestException('No IDs provided');
        }
        
        // Check permissions
        $currentUser = $this->ci->currentUser;
        if (!$this->ci->authorizer->checkAccess($currentUser, 'delete_registration')) {
            throw new ForbiddenException();
        }
        
        // Load registrations
        $registrations = Registration::whereIn('id', $ids)->get();
        
        $deletedCount = 0;
        $errors = [];
        
        foreach ($registrations as $registration) {
            try {
                // Check if can be deleted
                if ($registration->status == 'approved') {
                    $errors[] = "Cannot delete approved registration #{$registration->id}";
                    continue;
                }
                
                // Delete
                $registration->delete();
                $deletedCount++;
                
            } catch (\Exception $e) {
                $errors[] = "Error deleting registration #{$registration->id}: {$e->getMessage()}";
            }
        }
        
        // Return result
        return $response->withJson([
            'deleted' => $deletedCount,
            'errors' => $errors
        ], 200);
    }
}
```

### Batch Update

```php
class BatchUpdateAction
{
    public function __invoke(Request $request, Response $response, array $args)
    {
        // Get data
        $params = $request->getParsedBody();
        $ids = $params['ids'] ?? [];
        $updates = $params['updates'] ?? [];
        
        if (empty($ids) || empty($updates)) {
            throw new BadRequestException('Invalid request');
        }
        
        // Check permissions
        $currentUser = $this->ci->currentUser;
        if (!$this->ci->authorizer->checkAccess($currentUser, 'update_registration')) {
            throw new ForbiddenException();
        }
        
        // Update registrations
        $updatedCount = Registration::whereIn('id', $ids)
            ->update($updates);
        
        return $response->withJson([
            'updated' => $updatedCount
        ], 200);
    }
}
```

## Error Handling in CRUD

### Standard Error Responses

```php
// Not Found (404)
if (!$registration) {
    throw new NotFoundException('Registration not found');
}

// Forbidden (403)
if (!$this->authorizer->checkAccess($currentUser, 'permission')) {
    throw new ForbiddenException('Access denied');
}

// Bad Request (400)
if (!$validator->validate($data)) {
    return $response->withJson([
        'errors' => $validator->errors()
    ], 400);
}

// Internal Server Error (500)
try {
    // operation
} catch (\Exception $e) {
    $this->logger->error($e->getMessage());
    throw new InternalServerErrorException('Operation failed');
}
```

### Frontend Error Display

```javascript
// Handle AJAX errors
$.ajax({
    url: site.uri.public + '/api/registrations',
    method: 'POST',
    data: formData,
    success: function(response) {
        showAlert('Success!', 'success');
    },
    error: function(xhr) {
        var message = 'An error occurred';
        
        if (xhr.responseJSON) {
            // Validation errors
            if (xhr.responseJSON.errors) {
                displayValidationErrors(xhr.responseJSON.errors);
                return;
            }
            
            // General error message
            if (xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
        }
        
        showAlert(message, 'error');
    }
});

function displayValidationErrors(errors) {
    $.each(errors, function(field, messages) {
        var $field = $('#' + field);
        $field.addClass('is-invalid');
        $field.after('<div class="invalid-feedback">' + messages.join('<br>') + '</div>');
    });
}
```

## Testing CRUD Operations

### Unit Tests

```php
namespace UserFrosting\Sprinkle\RegSevak\Tests;

use UserFrosting\Sprinkle\RegSevak\Database\Models\Registration;

class RegistrationCrudTest extends TestCase
{
    public function testCreateRegistration()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890'
        ];
        
        $response = $this->request('POST', '/api/registrations', $data);
        
        $this->assertResponseStatus(201);
        $this->assertJson($response->getBody());
        
        $registration = json_decode($response->getBody(), true);
        $this->assertEquals('John Doe', $registration['name']);
    }
    
    public function testUpdateRegistration()
    {
        $registration = Registration::factory()->create();
        
        $data = ['name' => 'Updated Name'];
        $response = $this->request('PUT', '/api/registrations/' . $registration->id, $data);
        
        $this->assertResponseStatus(200);
        
        $registration->refresh();
        $this->assertEquals('Updated Name', $registration->name);
    }
    
    public function testDeleteRegistration()
    {
        $registration = Registration::factory()->create();
        
        $response = $this->request('DELETE', '/api/registrations/' . $registration->id);
        
        $this->assertResponseStatus(200);
        $this->assertNull(Registration::find($registration->id));
    }
}
```

## Next Steps

To fully document CRUD operations in RegSevak:

1. Document all CRUD controllers and their routes
2. Review all validation schemas
3. Test all CRUD operations with different user roles
4. Document any special business logic
5. Review error handling patterns
6. Document batch operation capabilities
7. Test performance with large datasets

## Related Documentation

- [02-rsdashboard-flow.md](02-rsdashboard-flow.md) - Dashboard implementation
- [03-datatables-integration.md](03-datatables-integration.md) - DataTables usage
- [05-user-flows.md](05-user-flows.md) - User workflows
- [06-admin-flows.md](06-admin-flows.md) - Admin workflows
