# DataTables Integration in RegSevak

## Overview

RegSevak heavily uses DataTables (specifically UserFrosting's ufTable) for displaying and managing tabular data. This document details the DataTables implementation patterns, Sprunje integration, and common use cases.

## DataTables in UserFrosting 4.6.7

### Core Components

1. **jQuery DataTables** - Client-side table plugin
2. **ufTable** - UserFrosting wrapper for DataTables
3. **Sprunje** - Server-side processing layer
4. **Template Integration** - Twig helpers for tables

## ufTable Implementation

### Basic Table Structure

#### HTML Template (Twig)

```twig
{# templates/pages/registrations.html.twig #}

<div class="card">
    <div class="card-header">
        <h3>Registrations</h3>
        <div class="card-header-actions">
            <button class="btn btn-primary" data-toggle="modal" data-target="#create-registration-modal">
                <i class="fa fa-plus"></i> New Registration
            </button>
        </div>
    </div>
    <div class="card-body">
        <table id="registrations-table" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                {# DataTables will populate this via AJAX #}
            </tbody>
        </table>
    </div>
</div>
```

#### JavaScript Initialization

```javascript
// assets/js/pages/registrations.js

$(document).ready(function() {
    // Initialize ufTable
    $('#registrations-table').ufTable({
        dataUrl: site.uri.public + '/api/registrations',
        useLoadingTransition: true,
        tableId: 'registrations-table'
    });
});
```

## Sprunje Server-Side Processing

### What is Sprunje?

Sprunje is UserFrosting's server-side processing layer for DataTables. It handles:

- **Pagination** - Dividing large datasets into pages
- **Sorting** - Multi-column sorting
- **Filtering** - Global and column-specific filtering
- **Searching** - Full-text search across columns

### Sprunje Class Structure

```php
namespace UserFrosting\Sprinkle\RegSevak\Sprunje;

use UserFrosting\Sprinkle\Core\Sprunje\Sprunje;
use UserFrosting\Sprinkle\RegSevak\Database\Models\Registration;

class RegistrationSprunje extends Sprunje
{
    /**
     * @var string The name of this Sprunje
     */
    protected $name = 'registrations';
    
    /**
     * @var array Fields that can be sorted
     */
    protected $sortable = [
        'id',
        'user_name',
        'status',
        'created_at',
        'updated_at'
    ];
    
    /**
     * @var array Fields that can be filtered
     */
    protected $filterable = [
        'user_name',
        'status',
        'email'
    ];
    
    /**
     * @var array Default sort order
     */
    protected $sorts = [
        'created_at' => 'desc'
    ];
    
    /**
     * Set the initial query used by the Sprunje
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function baseQuery()
    {
        // Base query with relationships
        $query = Registration::with('user', 'status');
        
        // Apply user-based filtering if not admin
        if (!$this->currentUser->hasRole('admin')) {
            $query->where('user_id', $this->currentUser->id);
        }
        
        return $query;
    }
    
    /**
     * Filter by user name
     *
     * @param Builder $query
     * @param mixed $value
     * @return Builder
     */
    protected function filterUserName($query, $value)
    {
        return $query->whereHas('user', function ($q) use ($value) {
            $q->where('user_name', 'LIKE', "%{$value}%");
        });
    }
    
    /**
     * Filter by status
     *
     * @param Builder $query
     * @param mixed $value
     * @return Builder
     */
    protected function filterStatus($query, $value)
    {
        return $query->where('status', $value);
    }
    
    /**
     * Filter by date range
     *
     * @param Builder $query
     * @param mixed $value
     * @return Builder
     */
    protected function filterCreatedAt($query, $value)
    {
        // Expecting value like "2023-01-01,2023-12-31"
        $dates = explode(',', $value);
        if (count($dates) == 2) {
            return $query->whereBetween('created_at', $dates);
        }
        return $query;
    }
}
```

### API Endpoint for Sprunje

```php
namespace UserFrosting\Sprinkle\RegSevak\Controller\Api;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use UserFrosting\Sprinkle\RegSevak\Sprunje\RegistrationSprunje;

class RegistrationsListAction
{
    protected $ci;
    
    public function __construct($ci)
    {
        $this->ci = $ci;
    }
    
    public function __invoke(Request $request, Response $response, array $args)
    {
        // Get query parameters from DataTables
        $params = $request->getQueryParams();
        
        // Create Sprunje instance
        $sprunje = new RegistrationSprunje($this->ci, $params);
        
        // Get results as JSON
        return $sprunje->toResponse($response);
    }
}
```

### Route Definition

```php
// config/routes.php

$app->get('/api/registrations', 'UserFrosting\Sprinkle\RegSevak\Controller\Api\RegistrationsListAction')
    ->setName('api.registrations.list')
    ->add('authGuard');
```

## Advanced ufTable Features

### Custom Columns

```javascript
$('#registrations-table').ufTable({
    dataUrl: site.uri.public + '/api/registrations',
    useLoadingTransition: true,
    tableId: 'registrations-table',
    columns: [
        {
            data: 'id',
            title: 'ID',
            orderable: true
        },
        {
            data: 'user_name',
            title: 'User',
            orderable: true,
            render: function(data, type, row) {
                return '<a href="' + site.uri.public + '/users/' + row.user_id + '">' + data + '</a>';
            }
        },
        {
            data: 'status',
            title: 'Status',
            orderable: true,
            render: function(data, type, row) {
                var badges = {
                    'pending': 'badge-warning',
                    'approved': 'badge-success',
                    'rejected': 'badge-danger'
                };
                return '<span class="badge ' + badges[data] + '">' + data.toUpperCase() + '</span>';
            }
        },
        {
            data: 'created_at',
            title: 'Submitted',
            orderable: true,
            render: function(data, type, row) {
                return moment(data).format('YYYY-MM-DD HH:mm');
            }
        },
        {
            data: null,
            title: 'Actions',
            orderable: false,
            render: function(data, type, row) {
                var actions = '';
                actions += '<button class="btn btn-sm btn-primary btn-view" data-id="' + row.id + '"><i class="fa fa-eye"></i></button> ';
                actions += '<button class="btn btn-sm btn-success btn-edit" data-id="' + row.id + '"><i class="fa fa-edit"></i></button> ';
                actions += '<button class="btn btn-sm btn-danger btn-delete" data-id="' + row.id + '"><i class="fa fa-trash"></i></button>';
                return actions;
            }
        }
    ]
});
```

### Action Button Bindings

```javascript
// Bind action buttons after table is drawn
$('#registrations-table').on('draw.dt', function() {
    // View button
    $('.btn-view').click(function() {
        var id = $(this).data('id');
        viewRegistration(id);
    });
    
    // Edit button
    $('.btn-edit').click(function() {
        var id = $(this).data('id');
        editRegistration(id);
    });
    
    // Delete button
    $('.btn-delete').click(function() {
        var id = $(this).data('id');
        deleteRegistration(id);
    });
});

function viewRegistration(id) {
    // Load registration details modal
    $('#registration-view-modal').ufModal({
        sourceUrl: site.uri.public + '/api/registrations/' + id
    });
}

function editRegistration(id) {
    // Load registration edit modal
    $('#registration-edit-modal').ufModal({
        sourceUrl: site.uri.public + '/modals/registrations/' + id + '/edit'
    });
}

function deleteRegistration(id) {
    // Confirm and delete
    if (confirm('Are you sure you want to delete this registration?')) {
        $.ajax({
            url: site.uri.public + '/api/registrations/' + id,
            method: 'DELETE',
            success: function() {
                // Refresh table
                $('#registrations-table').DataTable().ajax.reload();
                
                // Show success message
                showAlert('Registration deleted successfully', 'success');
            }
        });
    }
}
```

## Column Filtering

### Individual Column Filters

```javascript
$('#registrations-table').ufTable({
    dataUrl: site.uri.public + '/api/registrations',
    useLoadingTransition: true,
    tableId: 'registrations-table',
    columnFilters: {
        status: {
            type: 'select',
            options: [
                { value: '', label: 'All' },
                { value: 'pending', label: 'Pending' },
                { value: 'approved', label: 'Approved' },
                { value: 'rejected', label: 'Rejected' }
            ]
        },
        created_at: {
            type: 'daterange'
        }
    }
});
```

### Global Search

```twig
<div class="card-header">
    <div class="input-group">
        <input type="text" id="table-search" class="form-control" placeholder="Search...">
        <div class="input-group-append">
            <button class="btn btn-primary" type="button">
                <i class="fa fa-search"></i>
            </button>
        </div>
    </div>
</div>
```

```javascript
// Connect search box to DataTable
$('#table-search').on('keyup', function() {
    $('#registrations-table').DataTable().search(this.value).draw();
});
```

## Responsive DataTables

### Responsive Configuration

```javascript
$('#registrations-table').ufTable({
    dataUrl: site.uri.public + '/api/registrations',
    useLoadingTransition: true,
    tableId: 'registrations-table',
    responsive: true,
    columns: [
        {
            data: 'id',
            title: 'ID',
            className: 'none' // Hide on small screens
        },
        {
            data: 'user_name',
            title: 'User',
            className: 'all' // Always visible
        },
        {
            data: 'status',
            title: 'Status',
            className: 'all'
        },
        {
            data: 'created_at',
            title: 'Submitted',
            className: 'desktop' // Show on desktop only
        }
    ]
});
```

## Export Functionality

### Export Buttons

```javascript
$('#registrations-table').ufTable({
    dataUrl: site.uri.public + '/api/registrations',
    useLoadingTransition: true,
    tableId: 'registrations-table',
    buttons: [
        {
            extend: 'excel',
            text: '<i class="fa fa-file-excel"></i> Export to Excel',
            exportOptions: {
                columns: ':visible'
            }
        },
        {
            extend: 'pdf',
            text: '<i class="fa fa-file-pdf"></i> Export to PDF',
            exportOptions: {
                columns: ':visible'
            }
        },
        {
            extend: 'csv',
            text: '<i class="fa fa-file-csv"></i> Export to CSV'
        },
        {
            extend: 'print',
            text: '<i class="fa fa-print"></i> Print'
        }
    ]
});
```

## Batch Operations

### Row Selection

```javascript
$('#registrations-table').ufTable({
    dataUrl: site.uri.public + '/api/registrations',
    useLoadingTransition: true,
    tableId: 'registrations-table',
    select: {
        style: 'multi',
        selector: 'td:first-child'
    },
    columns: [
        {
            data: null,
            defaultContent: '',
            className: 'select-checkbox',
            orderable: false
        },
        // ... other columns
    ]
});

// Batch approve button
$('#btn-batch-approve').click(function() {
    var table = $('#registrations-table').DataTable();
    var selectedRows = table.rows({ selected: true }).data();
    var ids = [];
    
    selectedRows.each(function(row) {
        ids.push(row.id);
    });
    
    if (ids.length > 0) {
        batchApproveRegistrations(ids);
    } else {
        alert('Please select at least one registration');
    }
});

function batchApproveRegistrations(ids) {
    $.ajax({
        url: site.uri.public + '/api/registrations/batch/approve',
        method: 'POST',
        data: { ids: ids },
        success: function() {
            $('#registrations-table').DataTable().ajax.reload();
            showAlert('Registrations approved successfully', 'success');
        }
    });
}
```

## Performance Optimization

### Server-Side Processing Benefits

1. **Large Datasets** - Handle millions of records
2. **Reduced Memory** - Only load visible rows
3. **Faster Initial Load** - Don't load all data upfront
4. **Database Optimization** - Use database for sorting/filtering

### Caching Strategies

```php
class RegistrationSprunje extends Sprunje
{
    protected function baseQuery()
    {
        // Cache query results
        $cacheKey = 'registrations_sprunje_' . $this->currentUser->id;
        
        return Cache::remember($cacheKey, 60, function () {
            return Registration::with('user', 'status');
        });
    }
}
```

### Pagination Settings

```javascript
$('#registrations-table').ufTable({
    dataUrl: site.uri.public + '/api/registrations',
    useLoadingTransition: true,
    tableId: 'registrations-table',
    pageLength: 25,
    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
    paging: true
});
```

## Common DataTables Patterns in RegSevak

### Pattern 1: Master-Detail Tables

```javascript
// Master table (registrations)
$('#registrations-table').ufTable({
    dataUrl: site.uri.public + '/api/registrations',
    // ... configuration
});

// When row is clicked, show detail table
$('#registrations-table tbody').on('click', 'tr', function() {
    var data = $('#registrations-table').DataTable().row(this).data();
    showRegistrationDetails(data.id);
});

function showRegistrationDetails(id) {
    // Initialize detail table
    $('#registration-details-table').ufTable({
        dataUrl: site.uri.public + '/api/registrations/' + id + '/details',
        // ... configuration
    });
}
```

### Pattern 2: Inline Editing

```javascript
$('#registrations-table').on('click', '.editable-field', function() {
    var cell = $(this);
    var originalValue = cell.text();
    var field = cell.data('field');
    var id = cell.data('id');
    
    // Replace with input
    var input = $('<input type="text" class="form-control">').val(originalValue);
    cell.html(input);
    input.focus();
    
    // Handle save
    input.blur(function() {
        var newValue = $(this).val();
        updateField(id, field, newValue, cell, originalValue);
    });
});

function updateField(id, field, value, cell, originalValue) {
    $.ajax({
        url: site.uri.public + '/api/registrations/' + id,
        method: 'PUT',
        data: { [field]: value },
        success: function() {
            cell.text(value);
            showAlert('Updated successfully', 'success');
        },
        error: function() {
            cell.text(originalValue);
            showAlert('Update failed', 'error');
        }
    });
}
```

### Pattern 3: Nested Tables (Expandable Rows)

```javascript
function formatDetailRow(data) {
    // Create nested table HTML
    return '<table class="table table-sm">' +
           '<tr><td>Field 1:</td><td>' + data.field1 + '</td></tr>' +
           '<tr><td>Field 2:</td><td>' + data.field2 + '</td></tr>' +
           '</table>';
}

$('#registrations-table tbody').on('click', 'td.details-control', function() {
    var tr = $(this).closest('tr');
    var row = table.row(tr);
    
    if (row.child.isShown()) {
        // Close row
        row.child.hide();
        tr.removeClass('shown');
    } else {
        // Open row
        row.child(formatDetailRow(row.data())).show();
        tr.addClass('shown');
    }
});
```

## Error Handling

### AJAX Error Handling

```javascript
$('#registrations-table').ufTable({
    dataUrl: site.uri.public + '/api/registrations',
    useLoadingTransition: true,
    tableId: 'registrations-table',
    ajax: {
        error: function(xhr, error, thrown) {
            console.error('DataTable loading error:', error);
            showAlert('Failed to load data. Please try again.', 'error');
        }
    }
});
```

### Sprunje Error Handling

```php
protected function baseQuery()
{
    try {
        return Registration::with('user', 'status');
    } catch (\Exception $e) {
        $this->ci->logger->error('Sprunje error: ' . $e->getMessage());
        throw new InternalServerErrorException('Failed to load registrations');
    }
}
```

## Testing DataTables

### Test Scenarios

1. **Load Test** - Verify table loads data correctly
2. **Sort Test** - Verify sorting works on all sortable columns
3. **Filter Test** - Verify filtering works correctly
4. **Pagination Test** - Verify pagination navigation
5. **Search Test** - Verify global search functionality
6. **Action Test** - Verify action buttons work correctly

### Example Test

```php
public function testRegistrationTableLoadsData()
{
    $response = $this->get('/api/registrations');
    
    $this->assertResponseOk();
    $this->assertJsonStructure([
        'count',
        'count_filtered',
        'rows' => [
            '*' => ['id', 'user_name', 'status', 'created_at']
        ]
    ]);
}
```

## Next Steps

To fully document DataTables usage in RegSevak:

1. Identify all tables in the application
2. Document each Sprunje class and its filters
3. Review custom column renderers
4. Document all action button handlers
5. Test and document performance characteristics
6. Document any custom DataTables plugins used

## Related Documentation

- [02-rsdashboard-flow.md](02-rsdashboard-flow.md) - Dashboard implementation
- [04-crud-operations.md](04-crud-operations.md) - CRUD operations
- [DataTables Documentation](https://datatables.net/)
- [UserFrosting Sprunje Guide](https://learn.userfrosting.com/4.6/database/data-sprunjing)
