# User Flows in RegSevak

## Overview

This document details the typical user workflows in the RegSevak application. Users are non-administrative participants who submit and manage their own registrations.

## User Personas

### Primary User
- **Role**: Regular user (non-admin)
- **Access Level**: Limited to own registrations
- **Primary Goal**: Submit and track registration applications

### User Permissions

Typical user permissions:
```
uri_dashboard          - Access to dashboard
create_registration    - Submit new registrations
view_own_registration  - View their own registrations
update_own_registration - Edit pending registrations
upload_documents       - Upload supporting documents
```

## Primary User Workflows

### 1. User Registration and Login

#### Flow Diagram
```
New User → Register Account → Verify Email → Login → Dashboard
```

#### Steps

1. **Account Registration**
   - Navigate to registration page
   - Fill user registration form (username, email, password)
   - Submit form
   - Receive verification email

2. **Email Verification**
   - Check email inbox
   - Click verification link
   - Account activated

3. **First Login**
   - Navigate to login page
   - Enter credentials
   - Redirected to /rsdashboard

### 2. Submit New Registration

#### Flow Diagram
```
Login → Dashboard → New Registration Button → Fill Form → Submit → Confirmation
```

#### Detailed Steps

1. **Access Registration Form**
   ```
   User clicks "New Registration" button on dashboard
   → Modal/page with registration form opens
   ```

2. **Fill Registration Form**
   - **Personal Information**
     - Name
     - Email
     - Phone number
     - Address
   
   - **Registration Details**
     - Registration type (if applicable)
     - Category/program selection
     - Additional custom fields
   
   - **Supporting Information**
     - Upload documents
     - Additional notes
     - Emergency contact (if required)

3. **Form Validation**
   - Client-side validation checks:
     - Required fields filled
     - Email format valid
     - Phone number format valid
     - File size limits respected
   
   - Server-side validation:
     - Duplicate check
     - Data type verification
     - Business rule validation

4. **Submit Registration**
   - User clicks "Submit" button
   - AJAX POST request to `/api/registrations`
   - Server creates registration record
   - Status set to "pending"

5. **Confirmation**
   - Success message displayed
   - Registration ID provided
   - Confirmation email sent
   - Dashboard updated with new registration

#### Code Example

```javascript
// Submit new registration
$('#new-registration-form').submit(function(e) {
    e.preventDefault();
    
    var formData = new FormData(this);
    
    $.ajax({
        url: site.uri.public + '/api/registrations',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            // Show success message
            showAlert('Registration submitted successfully! Reference ID: ' + response.id, 'success');
            
            // Close modal
            $('#new-registration-modal').modal('hide');
            
            // Refresh dashboard
            loadUserRegistrations();
            
            // Optionally redirect to detail page
            // window.location.href = site.uri.public + '/registrations/' + response.id;
        },
        error: function(xhr) {
            displayErrors(xhr.responseJSON.errors);
        }
    });
});
```

### 3. View Registration Status

#### Flow Diagram
```
Dashboard → My Registrations Table → Click Row → View Details
```

#### Steps

1. **Access Dashboard**
   - User logs in
   - Lands on /rsdashboard
   - Sees "My Registrations" table

2. **View Registrations List**
   - Table displays user's registrations
   - Columns shown:
     - Registration ID
     - Submission Date
     - Status
     - Last Updated
     - Actions

3. **Check Status**
   - Status displayed with color coding:
     - **Pending** - Yellow/Warning badge
     - **In Review** - Blue/Info badge
     - **Approved** - Green/Success badge
     - **Rejected** - Red/Danger badge

4. **View Details**
   - Click on registration row or "View" button
   - Details page shows:
     - All submitted information
     - Current status
     - Status history (if available)
     - Uploaded documents
     - Admin notes (if any)

### 4. Edit Pending Registration

#### Flow Diagram
```
My Registrations → Select Pending Registration → Edit Button → Modify → Save
```

#### Conditions for Editing
- Registration status must be "pending"
- User must have `update_own_registration` permission
- Edit window not closed (configurable deadline)

#### Steps

1. **Select Registration**
   - User navigates to their registrations
   - Identifies registration with "Pending" status

2. **Click Edit Button**
   - "Edit" button only visible for editable registrations
   - Modal opens with pre-filled form

3. **Modify Information**
   - User updates fields
   - Can upload additional documents
   - Can remove previously uploaded files (if allowed)

4. **Save Changes**
   - Click "Save" button
   - Validation runs
   - Updates saved to database
   - User notified of success

#### Code Example

```javascript
// Edit registration
function editRegistration(id) {
    $.ajax({
        url: site.uri.public + '/api/registrations/' + id,
        method: 'GET',
        success: function(registration) {
            // Check if editable
            if (registration.status !== 'pending') {
                showAlert('This registration can no longer be edited', 'warning');
                return;
            }
            
            // Populate form
            populateEditForm(registration);
            
            // Show modal
            $('#edit-registration-modal').modal('show');
        }
    });
}

// Save edited registration
$('#edit-registration-form').submit(function(e) {
    e.preventDefault();
    
    var id = $('#edit-registration-id').val();
    var formData = new FormData(this);
    
    $.ajax({
        url: site.uri.public + '/api/registrations/' + id,
        method: 'PUT',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            showAlert('Registration updated successfully', 'success');
            $('#edit-registration-modal').modal('hide');
            loadUserRegistrations();
        },
        error: function(xhr) {
            displayErrors(xhr.responseJSON.errors);
        }
    });
});
```

### 5. Upload Documents

#### Flow Diagram
```
Registration Detail → Upload Documents Section → Select Files → Upload → Confirm
```

#### Steps

1. **Navigate to Document Upload**
   - Open registration detail page
   - Locate "Documents" section

2. **Select Files**
   - Click "Upload Document" button
   - File picker dialog opens
   - Select one or more files

3. **Validate Files**
   - Check file types (PDF, JPG, PNG, etc.)
   - Check file sizes (max 5MB per file)
   - Display validation errors if any

4. **Upload**
   - Files uploaded via AJAX
   - Progress bar shown for each file
   - Success/error status for each upload

5. **Confirmation**
   - Uploaded documents listed
   - Download links available
   - Delete option (if allowed)

#### Code Example

```javascript
// Upload document
$('#document-upload-input').change(function() {
    var files = this.files;
    var registrationId = $('#registration-id').val();
    
    for (var i = 0; i < files.length; i++) {
        uploadDocument(registrationId, files[i]);
    }
});

function uploadDocument(registrationId, file) {
    // Validate file
    if (!validateFile(file)) {
        return;
    }
    
    var formData = new FormData();
    formData.append('document', file);
    formData.append('registration_id', registrationId);
    
    $.ajax({
        url: site.uri.public + '/api/documents/upload',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        xhr: function() {
            var xhr = new XMLHttpRequest();
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    var percentComplete = (e.loaded / e.total) * 100;
                    updateProgressBar(file.name, percentComplete);
                }
            }, false);
            return xhr;
        },
        success: function(response) {
            showAlert('Document uploaded successfully', 'success');
            addDocumentToList(response.document);
        },
        error: function(xhr) {
            showAlert('Upload failed: ' + xhr.responseJSON.message, 'error');
        }
    });
}

function validateFile(file) {
    // Check file type
    var allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
    if (allowedTypes.indexOf(file.type) === -1) {
        showAlert('Invalid file type. Please upload PDF, JPG, or PNG files.', 'error');
        return false;
    }
    
    // Check file size (5MB max)
    if (file.size > 5 * 1024 * 1024) {
        showAlert('File size exceeds 5MB limit', 'error');
        return false;
    }
    
    return true;
}
```

### 6. Withdraw Registration

#### Flow Diagram
```
My Registrations → Select Registration → Withdraw Button → Confirm → Registration Withdrawn
```

#### Conditions
- Only pending or in-review registrations can be withdrawn
- User must have permission
- Withdrawal might have deadline restrictions

#### Steps

1. **Select Registration**
   - Navigate to registrations list
   - Identify registration to withdraw

2. **Click Withdraw Button**
   - Confirmation dialog appears
   - Warning about withdrawal implications

3. **Confirm Withdrawal**
   - User confirms action
   - Status changed to "withdrawn"
   - Notification sent to admins

4. **Post-Withdrawal**
   - Registration marked as withdrawn
   - Cannot be edited further
   - May or may not be deletable

### 7. View Notifications

#### Types of Notifications

1. **Status Change Notifications**
   - Registration approved
   - Registration rejected
   - Additional information requested

2. **Document Notifications**
   - Document upload successful
   - Document rejected
   - New document required

3. **System Notifications**
   - Deadline reminders
   - Account updates
   - System announcements

#### Notification Display

```twig
{# Notification bell in header #}
<div class="notifications">
    <a href="#" class="notification-bell" data-toggle="dropdown">
        <i class="fa fa-bell"></i>
        {% if unread_count > 0 %}
            <span class="badge badge-danger">{{ unread_count }}</span>
        {% endif %}
    </a>
    <div class="dropdown-menu dropdown-menu-right">
        {% for notification in recent_notifications %}
            <a href="{{ notification.link }}" class="dropdown-item {{ notification.is_read ? '' : 'unread' }}">
                <i class="fa fa-{{ notification.icon }}"></i>
                {{ notification.message }}
                <small class="text-muted">{{ notification.created_at|date('M d, H:i') }}</small>
            </a>
        {% endfor %}
        <div class="dropdown-divider"></div>
        <a href="{{ site.uri.public }}/notifications" class="dropdown-item text-center">View All</a>
    </div>
</div>
```

### 8. Update Profile Information

#### Flow Diagram
```
Dashboard → Profile → Edit → Update → Save
```

#### Editable Profile Fields
- Name
- Email (may require re-verification)
- Phone number
- Address
- Password change
- Notification preferences

## User Journey Maps

### First-Time User Journey

```
1. Discover Application
   ↓
2. Create Account
   ↓
3. Verify Email
   ↓
4. Log In
   ↓
5. View Dashboard Tutorial (if available)
   ↓
6. Submit First Registration
   ↓
7. Upload Documents
   ↓
8. Monitor Status
   ↓
9. Receive Approval/Rejection
   ↓
10. Complete Process or Appeal
```

### Returning User Journey

```
1. Log In
   ↓
2. View Dashboard
   ↓
3. Check Registration Status
   ↓
4. [If Needed] Submit New Registration
   ↓
5. [If Needed] Upload Additional Documents
   ↓
6. Log Out
```

## Error States and Recovery

### Common Error Scenarios

1. **Duplicate Registration**
   - Message: "You have already submitted a registration for this period"
   - Action: View existing registration or contact admin

2. **Missing Required Documents**
   - Message: "Please upload all required documents"
   - Action: Upload missing documents

3. **Validation Errors**
   - Display field-specific error messages
   - Highlight invalid fields
   - Provide correction guidance

4. **Network Errors**
   - Message: "Connection lost. Please try again"
   - Action: Retry button, auto-retry with exponential backoff

## Mobile Experience

### Mobile-Specific Considerations

1. **Responsive Dashboard**
   - Stack widgets vertically
   - Simplify navigation
   - Touch-friendly buttons

2. **Mobile Form Entry**
   - Appropriate keyboard types (email, tel, number)
   - Mobile-optimized file upload
   - Auto-save drafts

3. **Mobile DataTables**
   - Responsive columns
   - Swipe gestures
   - Simplified filters

## Accessibility Considerations

1. **Screen Reader Support**
   - Proper ARIA labels
   - Semantic HTML
   - Form field labels

2. **Keyboard Navigation**
   - Tab order
   - Keyboard shortcuts
   - Focus indicators

3. **Visual Accessibility**
   - Color contrast
   - Font sizes
   - Alternative text for images

## Performance Considerations

1. **Dashboard Load Time**
   - Lazy load registrations table
   - Cache user data
   - Optimize API calls

2. **Form Submission**
   - Client-side validation before submission
   - Loading indicators
   - Prevent double submission

3. **Document Upload**
   - Chunked uploads for large files
   - Progress indicators
   - Background uploads

## Testing User Flows

### Test Cases

1. **Happy Path Tests**
   - Complete registration submission
   - Document upload
   - Status checking

2. **Error Path Tests**
   - Invalid form data
   - Duplicate submission
   - Permission errors

3. **Edge Cases**
   - Large file uploads
   - Network interruptions
   - Session timeout

## Next Steps

To complete user flow documentation:

1. Map all user screens and interactions
2. Document all error messages and handling
3. Create user flow diagrams (visual)
4. Test all workflows with real users
5. Document mobile-specific flows
6. Review accessibility compliance

## Related Documentation

- [02-rsdashboard-flow.md](02-rsdashboard-flow.md) - Dashboard details
- [04-crud-operations.md](04-crud-operations.md) - CRUD implementation
- [06-admin-flows.md](06-admin-flows.md) - Admin workflows
- [07-key-features.md](07-key-features.md) - Feature details
