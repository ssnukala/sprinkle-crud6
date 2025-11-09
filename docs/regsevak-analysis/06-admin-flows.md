# Admin Flows in RegSevak

## Overview

This document details the administrative workflows in the RegSevak application. Administrators have elevated privileges to manage registrations, users, and system configuration.

## Admin Personas

### Super Admin
- **Role**: Full system administrator
- **Access Level**: Unrestricted access
- **Primary Goal**: Manage entire registration system

### Registration Manager
- **Role**: Registration processing admin
- **Access Level**: Manage registrations, limited system access
- **Primary Goal**: Process and approve/reject registrations

### Support Admin
- **Role**: User support and assistance
- **Access Level**: View-only access, basic user support
- **Primary Goal**: Help users with registration issues

## Admin Permissions

Typical admin permissions:
```
uri_dashboard           - Access to admin dashboard
uri_registrations       - View all registrations
view_registration       - View any registration details
create_registration     - Create registrations for users
update_registration     - Edit any registration
update_registration_field - Update individual fields
delete_registration     - Delete registrations
approve_registration    - Approve registrations
reject_registration     - Reject registrations
export_registrations    - Export registration data
manage_users            - User management
manage_settings         - System configuration
view_reports            - Access to reports and analytics
```

## Primary Admin Workflows

### 1. Admin Dashboard Overview

#### Flow Diagram
```
Admin Login → /rsdashboard → Admin View → Statistics + Actions
```

#### Dashboard Components

1. **Statistics Widgets**
   - Total registrations count
   - Pending approvals count
   - Approved today count
   - Rejected count
   - Active users count

2. **Quick Actions**
   - Review pending registrations
   - Export data
   - Generate reports
   - User management
   - System settings

3. **Recent Activity**
   - Latest registrations
   - Recent approvals/rejections
   - User activity log
   - System alerts

#### Code Example

```twig
{# Admin Dashboard View #}
{% extends "pages/abstract/base.html.twig" %}

{% block body_content %}
<div class="container-fluid">
    {# Statistics Row #}
    <div class="row">
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-info"><i class="fa fa-users"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Registrations</span>
                    <span class="info-box-number">{{ total_registrations }}</span>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-warning"><i class="fa fa-clock"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Pending Approval</span>
                    <span class="info-box-number">{{ pending_count }}</span>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-success"><i class="fa fa-check"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Approved Today</span>
                    <span class="info-box-number">{{ approved_today }}</span>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-danger"><i class="fa fa-times"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Rejected</span>
                    <span class="info-box-number">{{ rejected_count }}</span>
                </div>
            </div>
        </div>
    </div>
    
    {# Pending Registrations Table #}
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3>Pending Registrations</h3>
                    <div class="card-header-actions">
                        <button class="btn btn-success" id="batch-approve">
                            <i class="fa fa-check"></i> Approve Selected
                        </button>
                        <button class="btn btn-danger" id="batch-reject">
                            <i class="fa fa-times"></i> Reject Selected
                        </button>
                        <button class="btn btn-primary" id="export-data">
                            <i class="fa fa-download"></i> Export
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table id="pending-registrations-table" class="table table-striped">
                        {# DataTable populated via AJAX #}
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
{% endblock %}
```

### 2. Review and Approve Registrations

#### Flow Diagram
```
Admin Dashboard → Pending Registrations → Review Details → Approve/Reject → Notify User
```

#### Detailed Steps

1. **Access Pending Registrations**
   - Navigate to pending registrations list
   - Filter by date, status, category (if applicable)
   - Sort by submission date (oldest first typically)

2. **Select Registration for Review**
   - Click on registration row
   - Detail view opens (modal or separate page)

3. **Review Registration Details**
   - **Personal Information**
     - Verify name, email, phone
     - Check address information
   
   - **Registration Specifics**
     - Review registration type/category
     - Verify eligibility criteria
     - Check for completeness
   
   - **Supporting Documents**
     - View uploaded documents
     - Verify document authenticity
     - Check for required documents
   
   - **Previous History** (if applicable)
     - Past registrations
     - User account status
     - Notes from previous reviews

4. **Make Decision**
   
   **Option A: Approve**
   - Click "Approve" button
   - Optional: Add approval notes
   - Confirm action
   - Registration status → "approved"
   - Notification sent to user
   
   **Option B: Reject**
   - Click "Reject" button
   - Required: Add rejection reason
   - Select rejection category
   - Confirm action
   - Registration status → "rejected"
   - Notification with reason sent to user
   
   **Option C: Request More Information**
   - Click "Request Info" button
   - Specify what information is needed
   - Registration status → "info_requested"
   - Notification sent to user
   
   **Option D: Put on Hold**
   - Click "Hold" button
   - Add hold reason
   - Registration status → "on_hold"
   - Admin notification for follow-up

5. **Post-Decision Actions**
   - Log decision in audit trail
   - Update statistics
   - Move to next registration
   - Generate reports if needed

#### Code Example

```javascript
// Review and approve/reject registration
function reviewRegistration(id) {
    // Load full registration details
    $.ajax({
        url: site.uri.public + '/api/registrations/' + id + '/details',
        method: 'GET',
        success: function(registration) {
            displayRegistrationReview(registration);
        }
    });
}

function displayRegistrationReview(registration) {
    // Populate review modal
    $('#review-modal-title').text('Review Registration #' + registration.id);
    $('#review-name').text(registration.name);
    $('#review-email').text(registration.email);
    $('#review-phone').text(registration.phone);
    // ... populate other fields
    
    // Load documents
    loadDocuments(registration.id);
    
    // Show modal
    $('#registration-review-modal').modal('show');
    
    // Store current registration ID
    $('#registration-review-modal').data('registration-id', registration.id);
}

// Approve registration
$('#btn-approve').click(function() {
    var id = $('#registration-review-modal').data('registration-id');
    var notes = $('#approval-notes').val();
    
    $.ajax({
        url: site.uri.public + '/api/registrations/' + id + '/approve',
        method: 'POST',
        data: { notes: notes },
        success: function(response) {
            showAlert('Registration approved successfully', 'success');
            $('#registration-review-modal').modal('hide');
            refreshPendingTable();
            updateStatistics();
        }
    });
});

// Reject registration
$('#btn-reject').click(function() {
    var id = $('#registration-review-modal').data('registration-id');
    var reason = $('#rejection-reason').val();
    var category = $('#rejection-category').val();
    
    if (!reason) {
        showAlert('Please provide a rejection reason', 'warning');
        return;
    }
    
    $.ajax({
        url: site.uri.public + '/api/registrations/' + id + '/reject',
        method: 'POST',
        data: { 
            reason: reason,
            category: category 
        },
        success: function(response) {
            showAlert('Registration rejected', 'success');
            $('#registration-review-modal').modal('hide');
            refreshPendingTable();
            updateStatistics();
        }
    });
});

// Request more information
$('#btn-request-info').click(function() {
    var id = $('#registration-review-modal').data('registration-id');
    var requestedInfo = $('#requested-information').val();
    
    $.ajax({
        url: site.uri.public + '/api/registrations/' + id + '/request-info',
        method: 'POST',
        data: { requested_info: requestedInfo },
        success: function(response) {
            showAlert('Information request sent to user', 'success');
            $('#registration-review-modal').modal('hide');
            refreshPendingTable();
        }
    });
});
```

### 3. Batch Operations

#### Batch Approval Flow
```
Select Multiple Registrations → Click Batch Approve → Confirm → Process → Update
```

#### Implementation

```javascript
// Initialize DataTable with selection
$('#pending-registrations-table').ufTable({
    dataUrl: site.uri.public + '/api/registrations?status=pending',
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

// Batch approve
$('#batch-approve').click(function() {
    var table = $('#pending-registrations-table').DataTable();
    var selectedRows = table.rows({ selected: true }).data();
    var ids = [];
    
    selectedRows.each(function(row) {
        ids.push(row.id);
    });
    
    if (ids.length === 0) {
        showAlert('Please select at least one registration', 'warning');
        return;
    }
    
    if (!confirm('Are you sure you want to approve ' + ids.length + ' registrations?')) {
        return;
    }
    
    $.ajax({
        url: site.uri.public + '/api/registrations/batch/approve',
        method: 'POST',
        data: { ids: ids },
        success: function(response) {
            showAlert(response.approved + ' registrations approved', 'success');
            if (response.errors.length > 0) {
                showAlert('Some registrations could not be approved', 'warning');
                console.log(response.errors);
            }
            table.ajax.reload();
            updateStatistics();
        }
    });
});

// Batch reject
$('#batch-reject').click(function() {
    var table = $('#pending-registrations-table').DataTable();
    var selectedRows = table.rows({ selected: true }).data();
    var ids = [];
    
    selectedRows.each(function(row) {
        ids.push(row.id);
    });
    
    if (ids.length === 0) {
        showAlert('Please select at least one registration', 'warning');
        return;
    }
    
    // Show batch reject modal for reason
    $('#batch-reject-modal').data('ids', ids).modal('show');
});

$('#confirm-batch-reject').click(function() {
    var ids = $('#batch-reject-modal').data('ids');
    var reason = $('#batch-reject-reason').val();
    
    if (!reason) {
        showAlert('Please provide a rejection reason', 'warning');
        return;
    }
    
    $.ajax({
        url: site.uri.public + '/api/registrations/batch/reject',
        method: 'POST',
        data: { 
            ids: ids,
            reason: reason
        },
        success: function(response) {
            showAlert(response.rejected + ' registrations rejected', 'success');
            $('#batch-reject-modal').modal('hide');
            $('#pending-registrations-table').DataTable().ajax.reload();
            updateStatistics();
        }
    });
});
```

### 4. Search and Filter Registrations

#### Advanced Search Features

1. **Status Filter**
   - Pending
   - Approved
   - Rejected
   - On Hold
   - Info Requested

2. **Date Range Filter**
   - Submission date
   - Last updated date
   - Approval/rejection date

3. **Category Filter**
   - Registration type
   - Program/category
   - Priority level

4. **User Filter**
   - User name
   - Email
   - User ID

5. **Text Search**
   - Global search across all fields
   - Specific field search

#### Implementation

```javascript
// Advanced filters
$('#filter-status').change(function() {
    var status = $(this).val();
    var table = $('#registrations-table').DataTable();
    table.column('status:name').search(status).draw();
});

$('#filter-date-range').daterangepicker({
    autoUpdateInput: false,
    locale: {
        cancelLabel: 'Clear'
    }
});

$('#filter-date-range').on('apply.daterangepicker', function(ev, picker) {
    var startDate = picker.startDate.format('YYYY-MM-DD');
    var endDate = picker.endDate.format('YYYY-MM-DD');
    
    var table = $('#registrations-table').DataTable();
    // Apply custom date range filter
    $.fn.dataTable.ext.search.push(
        function(settings, data, dataIndex) {
            var submittedDate = data[3]; // Assuming date is in column 3
            return (submittedDate >= startDate && submittedDate <= endDate);
        }
    );
    table.draw();
});

// Clear filters
$('#clear-filters').click(function() {
    $('#filter-status').val('');
    $('#filter-date-range').val('');
    $('#search-text').val('');
    
    $.fn.dataTable.ext.search.pop();
    var table = $('#registrations-table').DataTable();
    table.search('').columns().search('').draw();
});
```

### 5. Export Registration Data

#### Export Formats
- Excel (.xlsx)
- CSV (.csv)
- PDF report
- JSON data

#### Flow Diagram
```
Select Filters → Choose Export Format → Click Export → Generate File → Download
```

#### Implementation

```javascript
// Export to Excel
$('#export-excel').click(function() {
    var filters = getActiveFilters();
    
    window.location.href = site.uri.public + '/api/registrations/export/excel?' + $.param(filters);
});

// Export to CSV
$('#export-csv').click(function() {
    var filters = getActiveFilters();
    
    window.location.href = site.uri.public + '/api/registrations/export/csv?' + $.param(filters);
});

// Export to PDF
$('#export-pdf').click(function() {
    var filters = getActiveFilters();
    
    $.ajax({
        url: site.uri.public + '/api/registrations/export/pdf',
        method: 'POST',
        data: filters,
        xhrFields: {
            responseType: 'blob'
        },
        success: function(blob) {
            var link = document.createElement('a');
            link.href = window.URL.createObjectURL(blob);
            link.download = 'registrations_' + new Date().toISOString().split('T')[0] + '.pdf';
            link.click();
        }
    });
});

function getActiveFilters() {
    return {
        status: $('#filter-status').val(),
        date_from: $('#filter-date-from').val(),
        date_to: $('#filter-date-to').val(),
        category: $('#filter-category').val(),
        search: $('#search-text').val()
    };
}
```

### 6. User Management

#### User Management Tasks

1. **View All Users**
   - List all registered users
   - Filter by role, status, registration count
   - Search by name, email

2. **Create User Account**
   - Manual user creation
   - Bulk user import
   - Assign roles and permissions

3. **Edit User**
   - Update user information
   - Change password
   - Modify roles and permissions

4. **Enable/Disable User**
   - Activate user account
   - Deactivate user account
   - Temporary suspension

5. **Delete User**
   - Soft delete (recommended)
   - Hard delete (with confirmation)
   - Handle associated registrations

#### Code Example

```php
// Admin User Management Controller
namespace UserFrosting\Sprinkle\RegSevak\Controller\Admin;

class UserManagementAction
{
    public function __invoke(Request $request, Response $response, array $args)
    {
        // Check admin permission
        if (!$this->ci->authorizer->checkAccess($currentUser, 'manage_users')) {
            throw new ForbiddenException();
        }
        
        // Get users list with Sprunje
        $params = $request->getQueryParams();
        $sprunje = new UserSprunje($this->ci, $params);
        
        return $sprunje->toResponse($response);
    }
}
```

### 7. Reports and Analytics

#### Available Reports

1. **Registration Statistics**
   - Total registrations by period
   - Approval/rejection rates
   - Average processing time
   - Status distribution

2. **User Analytics**
   - New user registrations
   - Active users
   - User engagement metrics

3. **Performance Metrics**
   - Admin processing speed
   - Bottleneck identification
   - Peak submission times

4. **Custom Reports**
   - Ad-hoc queries
   - Scheduled reports
   - Exported data analysis

#### Dashboard Charts

```javascript
// Registration trends chart
function initRegistrationTrendsChart() {
    $.ajax({
        url: site.uri.public + '/api/reports/registration-trends',
        method: 'GET',
        data: {
            period: '30days'
        },
        success: function(data) {
            var ctx = document.getElementById('registration-trends-chart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.dates,
                    datasets: [{
                        label: 'Registrations',
                        data: data.counts,
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Registration Trends (Last 30 Days)'
                        }
                    }
                }
            });
        }
    });
}

// Status distribution pie chart
function initStatusDistributionChart() {
    $.ajax({
        url: site.uri.public + '/api/reports/status-distribution',
        method: 'GET',
        success: function(data) {
            var ctx = document.getElementById('status-distribution-chart').getContext('2d');
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: data.statuses,
                    datasets: [{
                        data: data.counts,
                        backgroundColor: [
                            '#ffc107', // pending - yellow
                            '#28a745', // approved - green
                            '#dc3545', // rejected - red
                            '#17a2b8', // info requested - blue
                            '#6c757d'  // on hold - gray
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Registration Status Distribution'
                        }
                    }
                }
            });
        }
    });
}
```

### 8. System Configuration

#### Configurable Settings

1. **Registration Settings**
   - Enable/disable registration periods
   - Set registration deadlines
   - Configure registration types
   - Set validation rules

2. **Notification Settings**
   - Email templates
   - Notification triggers
   - Admin notification preferences

3. **Document Settings**
   - Allowed file types
   - Maximum file size
   - Required documents list

4. **Workflow Settings**
   - Approval workflow stages
   - Auto-approval rules
   - Escalation rules

#### Settings Interface

```twig
{# System settings page #}
<div class="card">
    <div class="card-header">
        <h3>Registration Settings</h3>
    </div>
    <div class="card-body">
        <form id="settings-form">
            <div class="form-group">
                <label>Registration Period Status</label>
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="registration-enabled" 
                           name="registration_enabled" {{ settings.registration_enabled ? 'checked' : '' }}>
                    <label class="custom-control-label" for="registration-enabled">
                        Enable Registration
                    </label>
                </div>
            </div>
            
            <div class="form-group">
                <label for="registration-deadline">Registration Deadline</label>
                <input type="date" class="form-control" id="registration-deadline" 
                       name="registration_deadline" value="{{ settings.registration_deadline }}">
            </div>
            
            <div class="form-group">
                <label for="max-file-size">Maximum File Size (MB)</label>
                <input type="number" class="form-control" id="max-file-size" 
                       name="max_file_size" value="{{ settings.max_file_size }}">
            </div>
            
            <button type="submit" class="btn btn-primary">Save Settings</button>
        </form>
    </div>
</div>
```

### 9. Audit Trail and Logging

#### Logged Actions

1. **Registration Actions**
   - Created
   - Updated
   - Approved
   - Rejected
   - Deleted

2. **User Actions**
   - Login/logout
   - Password changes
   - Permission changes

3. **System Actions**
   - Configuration changes
   - Data exports
   - Batch operations

#### Audit Log View

```javascript
// View audit trail
function viewAuditTrail(registrationId) {
    $.ajax({
        url: site.uri.public + '/api/audit/registration/' + registrationId,
        method: 'GET',
        success: function(logs) {
            displayAuditLogs(logs);
        }
    });
}

function displayAuditLogs(logs) {
    var html = '<div class="timeline">';
    
    logs.forEach(function(log) {
        html += '<div class="timeline-item">';
        html += '<div class="timeline-marker"></div>';
        html += '<div class="timeline-content">';
        html += '<h5>' + log.action + '</h5>';
        html += '<p>By: ' + log.user_name + '</p>';
        html += '<p>' + log.description + '</p>';
        html += '<small class="text-muted">' + log.created_at + '</small>';
        html += '</div>';
        html += '</div>';
    });
    
    html += '</div>';
    
    $('#audit-trail-container').html(html);
    $('#audit-trail-modal').modal('show');
}
```

## Admin Best Practices

### 1. Review Efficiency
- Process registrations in chronological order
- Use batch operations for similar cases
- Set aside dedicated review time
- Use filters to prioritize urgent cases

### 2. Communication
- Provide clear rejection reasons
- Use templates for common responses
- Respond to user inquiries promptly
- Document special cases

### 3. Data Management
- Regular data exports for backup
- Periodic cleanup of old records
- Monitor system performance
- Review audit logs regularly

### 4. Security
- Use strong passwords
- Enable two-factor authentication
- Log out when not in use
- Review permission assignments

## Next Steps

To complete admin flow documentation:

1. Document all admin screens
2. Create workflow diagrams
3. Document all admin permissions
4. Test all admin operations
5. Document troubleshooting procedures
6. Create admin training materials

## Related Documentation

- [02-rsdashboard-flow.md](02-rsdashboard-flow.md) - Dashboard details
- [04-crud-operations.md](04-crud-operations.md) - CRUD operations
- [05-user-flows.md](05-user-flows.md) - User workflows
- [07-key-features.md](07-key-features.md) - Feature analysis
