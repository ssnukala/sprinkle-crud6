# /rsdashboard Flow Documentation

## Overview

The `/rsdashboard` route serves as the main entry point for the RegSevak application, providing a unified dashboard interface for both administrators and regular users. The flow is role-based, displaying different content and actions depending on user permissions.

## Route Definition

### Basic Route Structure

```php
// In RegSevak/config/routes.php
$app->get('/rsdashboard', 'UserFrosting\Sprinkle\RegSevak\Controller\DashboardAction')
    ->setName('rsdashboard')
    ->add('authGuard');
```

### Route Properties

- **Method**: GET
- **Path**: `/rsdashboard`
- **Controller**: DashboardAction (or similar)
- **Name**: `rsdashboard`
- **Middleware**: Authentication required (`authGuard`)

## Request Flow

### 1. User Accesses Dashboard

```
User Browser
    ↓
GET /rsdashboard
    ↓
Authentication Middleware
    ↓ (if authenticated)
Authorization Check
    ↓
DashboardAction Controller
    ↓
Data Collection
    ↓
Template Rendering
    ↓
HTML Response
```

### 2. Authentication Middleware

Before the controller executes:

1. **Session Check** - Verify user session exists
2. **User Validation** - Confirm user is logged in
3. **Redirect** - If not authenticated, redirect to login

```php
// Pseudo-code for auth check
if (!$this->auth->check()) {
    return redirect()->route('login');
}
```

### 3. Authorization Check

After authentication:

1. **Role Verification** - Determine user's role(s)
2. **Permission Check** - Verify access to dashboard
3. **Access Denial** - If unauthorized, show 403 error

```php
// Pseudo-code for authorization
if (!$authorizer->checkAccess($currentUser, 'uri_dashboard')) {
    throw new ForbiddenException();
}
```

## Controller Logic

### DashboardAction Structure

```php
namespace UserFrosting\Sprinkle\RegSevak\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DashboardAction
{
    protected $ci;
    
    public function __construct($ci)
    {
        $this->ci = $ci;
    }
    
    public function __invoke(Request $request, Response $response, array $args)
    {
        // 1. Get current user
        $currentUser = $this->ci->currentUser;
        
        // 2. Determine user role
        $isAdmin = $currentUser->hasRole('admin');
        
        // 3. Collect dashboard data based on role
        if ($isAdmin) {
            $data = $this->getAdminDashboardData();
        } else {
            $data = $this->getUserDashboardData($currentUser);
        }
        
        // 4. Add common data
        $data['current_user'] = $currentUser;
        $data['page_title'] = 'Dashboard';
        
        // 5. Render template
        return $this->ci->view->render($response, 'pages/rsdashboard.html.twig', $data);
    }
    
    protected function getAdminDashboardData()
    {
        return [
            'total_registrations' => Registration::count(),
            'pending_registrations' => Registration::where('status', 'pending')->count(),
            'recent_registrations' => Registration::orderBy('created_at', 'desc')->take(10)->get(),
            'statistics' => $this->calculateStatistics(),
        ];
    }
    
    protected function getUserDashboardData($user)
    {
        return [
            'user_registrations' => Registration::where('user_id', $user->id)->get(),
            'registration_status' => $this->getRegistrationStatus($user),
            'next_steps' => $this->determineNextSteps($user),
        ];
    }
}
```

## Data Flow for Different User Roles

### Admin User Flow

```
Admin Access → /rsdashboard
    ↓
Collect Admin Data:
    ├── Total Registrations Count
    ├── Pending Registrations Count
    ├── Recent Registrations List
    ├── Statistics Dashboard
    ├── Quick Action Links
    └── System Notifications
    ↓
Render Admin Dashboard Template
    ↓
Display:
    ├── Registration Overview Table (DataTables)
    ├── Statistical Widgets
    ├── Approval Queue
    ├── User Management Links
    └── System Settings Access
```

### Regular User Flow

```
User Access → /rsdashboard
    ↓
Collect User Data:
    ├── User's Registrations
    ├── Registration Status
    ├── Pending Actions
    ├── Next Steps Guide
    └── Personal Notifications
    ↓
Render User Dashboard Template
    ↓
Display:
    ├── Registration Status Card
    ├── Submit New Registration Button
    ├── My Registrations Table
    ├── Profile Information
    └── Help/Support Links
```

## Template Structure

### Admin Dashboard Template

```twig
{# templates/pages/rsdashboard.html.twig #}
{% extends "pages/abstract/base.html.twig" %}

{% block page_title %}{{ page_title }}{% endblock %}

{% block body_content %}
    <div class="container-fluid">
        {% if current_user.hasRole('admin') %}
            {# Admin Dashboard #}
            <div class="row">
                <div class="col-md-3">
                    {# Total Registrations Widget #}
                    {% include 'components/widgets/stat-card.html.twig' with {
                        'title': 'Total Registrations',
                        'value': total_registrations,
                        'icon': 'users'
                    } %}
                </div>
                <div class="col-md-3">
                    {# Pending Registrations Widget #}
                    {% include 'components/widgets/stat-card.html.twig' with {
                        'title': 'Pending Approval',
                        'value': pending_registrations,
                        'icon': 'clock'
                    } %}
                </div>
                {# More widgets... #}
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    {# Registrations DataTable #}
                    <div class="card">
                        <div class="card-header">
                            <h3>Recent Registrations</h3>
                        </div>
                        <div class="card-body">
                            <table id="registrations-table" class="table table-striped">
                                {# DataTable will populate this #}
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        {% else %}
            {# User Dashboard #}
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3>My Registrations</h3>
                            <button class="btn btn-primary" data-toggle="modal" data-target="#new-registration-modal">
                                New Registration
                            </button>
                        </div>
                        <div class="card-body">
                            <table id="my-registrations-table" class="table table-striped">
                                {# User's registrations #}
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        {% endif %}
    </div>
{% endblock %}

{% block scripts_page %}
    <script src="{{ assets.url('js/pages/dashboard.js') }}"></script>
{% endblock %}
```

## JavaScript Initialization

### Dashboard JavaScript

```javascript
// assets/js/pages/dashboard.js

$(document).ready(function() {
    // Initialize DataTables based on user role
    if (page.isAdmin) {
        initAdminDashboard();
    } else {
        initUserDashboard();
    }
});

function initAdminDashboard() {
    // Admin registrations DataTable
    $('#registrations-table').ufTable({
        dataUrl: site.uri.public + '/api/registrations',
        useLoadingTransition: true,
        tableId: 'registrations-table'
    });
    
    // Refresh statistics widgets
    refreshStatistics();
    
    // Set up auto-refresh
    setInterval(refreshStatistics, 60000); // Every minute
}

function initUserDashboard() {
    // User's registrations DataTable
    $('#my-registrations-table').ufTable({
        dataUrl: site.uri.public + '/api/registrations?user_id=' + page.userId,
        useLoadingTransition: true,
        tableId: 'my-registrations-table'
    });
    
    // Initialize registration form modal
    bindRegistrationFormModal();
}

function refreshStatistics() {
    $.ajax({
        url: site.uri.public + '/api/dashboard/statistics',
        method: 'GET',
        success: function(data) {
            updateStatisticsWidgets(data);
        }
    });
}
```

## API Endpoints Used by Dashboard

### For Admin Users

1. **GET /api/registrations**
   - Purpose: Load registrations DataTable
   - Response: Paginated registration list with Sprunje

2. **GET /api/dashboard/statistics**
   - Purpose: Fetch dashboard statistics
   - Response: JSON with counts and metrics

3. **GET /api/registrations/{id}**
   - Purpose: View registration details
   - Response: Single registration object

4. **PUT /api/registrations/{id}/status**
   - Purpose: Update registration status (approve/reject)
   - Request: { "status": "approved" }
   - Response: Updated registration

### For Regular Users

1. **GET /api/registrations?user_id={id}**
   - Purpose: Load user's registrations
   - Response: User's registration list

2. **POST /api/registrations**
   - Purpose: Create new registration
   - Request: Registration form data
   - Response: Created registration

3. **GET /api/registrations/{id}**
   - Purpose: View own registration details
   - Response: Registration object (if owned by user)

4. **PUT /api/registrations/{id}**
   - Purpose: Update own registration (if editable)
   - Request: Updated registration data
   - Response: Updated registration

## Permission Requirements

### Required Permissions

```php
// Admin users need:
'uri_dashboard'         // Access dashboard
'uri_registrations'     // View registrations list
'view_registration'     // View registration details
'update_registration'   // Modify registrations
'approve_registration'  // Approve/reject registrations

// Regular users need:
'uri_dashboard'         // Access dashboard
'create_registration'   // Submit new registrations
'view_own_registration' // View their registrations
'update_own_registration' // Edit their registrations (if allowed)
```

### Permission Checks in Template

```twig
{% if checkAccess('approve_registration') %}
    <button class="btn btn-success" data-action="approve">Approve</button>
    <button class="btn btn-danger" data-action="reject">Reject</button>
{% endif %}

{% if checkAccess('create_registration') %}
    <button class="btn btn-primary" data-toggle="modal" data-target="#new-registration-modal">
        New Registration
    </button>
{% endif %}
```

## State Management

### Dashboard States

1. **Loading State** - While fetching data
2. **Empty State** - No registrations exist
3. **Active State** - Normal operation with data
4. **Error State** - When API calls fail

### User Session Data

Stored in session and available to dashboard:

```php
// Session data structure
[
    'user_id' => 123,
    'user_name' => 'john_doe',
    'roles' => ['user'],
    'permissions' => [...],
    'last_activity' => timestamp,
    'dashboard_preferences' => [
        'default_view' => 'grid',
        'items_per_page' => 25,
    ]
]
```

## Error Handling

### Common Error Scenarios

1. **Not Authenticated**
   - Redirect to login page
   - Preserve intended destination

2. **Insufficient Permissions**
   - Display 403 Forbidden page
   - Log access attempt

3. **Data Loading Failure**
   - Show error message in UI
   - Provide retry option

4. **Network Errors**
   - Display connection error
   - Enable offline mode if applicable

## Notification System

### Dashboard Notifications

```twig
{# Display flash messages #}
{% for category, messages in flashMessages %}
    {% for message in messages %}
        <div class="alert alert-{{ category }}">
            {{ message }}
        </div>
    {% endfor %}
{% endfor %}
```

### Real-time Updates (if implemented)

```javascript
// WebSocket or polling for real-time updates
var socket = io.connect(site.uri.public);

socket.on('registration_updated', function(data) {
    // Refresh specific table row
    refreshTableRow('registrations-table', data.registration_id);
    
    // Show notification
    showNotification('Registration updated', 'info');
});
```

## Performance Considerations

### Optimization Strategies

1. **Lazy Loading** - Load DataTables data on demand
2. **Caching** - Cache statistics for short periods
3. **Pagination** - Limit records loaded at once
4. **Asynchronous Loading** - Load widgets independently
5. **Query Optimization** - Use eager loading for relationships

### Database Queries

```php
// Optimized query with eager loading
Registration::with(['user', 'status', 'documents'])
    ->where('status', 'pending')
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();
```

## Testing Considerations

### Test Scenarios

1. **Admin Access Test** - Verify admin sees all registrations
2. **User Access Test** - Verify user sees only their registrations
3. **Permission Test** - Verify unauthorized access is blocked
4. **Data Loading Test** - Verify correct data is displayed
5. **Action Test** - Verify CRUD operations work correctly

## Next Steps

To fully document the rsdashboard flow:

1. Examine actual controller implementation
2. Review template files for exact UI structure
3. Test all user roles and their views
4. Document all JavaScript interactions
5. Map all API endpoints and their responses
6. Document any custom widgets or components
7. Review and document notification system
8. Analyze and document caching strategy

## Related Documentation

- [01-overview.md](01-overview.md) - Application overview
- [03-datatables-integration.md](03-datatables-integration.md) - DataTables usage
- [04-crud-operations.md](04-crud-operations.md) - CRUD implementation
- [05-user-flows.md](05-user-flows.md) - Detailed user workflows
- [06-admin-flows.md](06-admin-flows.md) - Detailed admin workflows
