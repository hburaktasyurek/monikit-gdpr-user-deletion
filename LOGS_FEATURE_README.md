# Monikit GDPR User Data Deletion - Logging Feature

## 📋 Overview

The logging feature provides comprehensive audit trails for all deletion requests and actions, ensuring GDPR compliance and providing administrators with detailed insights into user data deletion activities.

## 🚀 Features

### ✅ Logging Capabilities

#### 📝 **Comprehensive Logging**
- **Deletion Requests**: Logs when users submit deletion requests (status: pending)
- **Email Confirmations**: Tracks email confirmation attempts (status: success/failed)
- **Keycloak Deletions**: Records successful and failed account deletions from Keycloak (status: success/failed)
- **Error Tracking**: Captures all errors and exceptions
- **IP Address Tracking**: Records client IP addresses for security
- **User Agent Logging**: Stores browser/client information

#### 🔍 **Detailed Information**
- **Email Addresses**: User email addresses (for audit purposes)
- **Action Types**: Request, confirmation, deletion, error, expired
- **Status Tracking**: Success, failed, pending
- **Keycloak Integration**: Keycloak user IDs and realm information
- **Request/Response Data**: Full request and response data in JSON format
- **Timestamps**: Precise timestamps for all actions

#### 📊 **Admin Interface**
- **Statistics Dashboard**: Overview of deletion activities
- **Filtering System**: Filter by email, action, status, and date range
- **Pagination**: Efficient handling of large log datasets
- **Export Functionality**: CSV export for compliance reporting
- **Log Details**: Detailed view of individual log entries
- **Cleanup Tools**: Automatic cleanup of old logs

## 📁 Database Structure

### Logs Table Schema

```sql
CREATE TABLE wp_monikit_deletion_logs (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    email varchar(255) NOT NULL,
    action varchar(50) NOT NULL,
    status varchar(50) NOT NULL,
    message text,
    ip_address varchar(45),
    user_agent text,
    request_data longtext,
    response_data longtext,
    keycloak_user_id varchar(255),
    keycloak_realm varchar(255),
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY email (email),
    KEY action (action),
    KEY status (status),
    KEY created_at (created_at)
);
```

### Field Descriptions

| Field | Type | Description |
|-------|------|-------------|
| `id` | bigint | Unique log entry identifier |
| `email` | varchar(255) | User's email address |
| `action` | varchar(50) | Action type (request, confirmation, deletion, error, expired) |
| `status` | varchar(50) | Status (success, failed, pending) |
| `message` | text | Human-readable message or error description |
| `ip_address` | varchar(45) | Client IP address |
| `user_agent` | text | Browser/client user agent string |
| `request_data` | longtext | JSON-encoded request data |
| `response_data` | longtext | JSON-encoded response data |
| `keycloak_user_id` | varchar(255) | Keycloak user ID (if available) |
| `keycloak_realm` | varchar(255) | Keycloak realm name |
| `created_at` | datetime | Timestamp of the log entry |

## 🔧 Usage

### Status Tracking Flow

The logging system now provides detailed status tracking for the complete deletion process:

#### 1. **Deletion Request** (Status: Pending)
- **When**: User submits email address
- **Action**: `request`
- **Status**: `pending`
- **Description**: "Deletion request submitted and confirmation email sent. Awaiting user confirmation."

#### 2. **Email Confirmation** (Status: Success/Failed)
- **When**: User enters confirmation code
- **Action**: `confirmation`
- **Status**: `success` (if code valid) or `failed` (if code invalid/expired)
- **Description**: "Email confirmation successful. Proceeding with account deletion."

#### 3. **Keycloak Deletion** (Status: Success/Failed)
- **When**: System attempts to delete account from Keycloak
- **Action**: `deletion`
- **Status**: `success` (if Keycloak deletion successful) or `failed` (if Keycloak deletion failed)
- **Description**: "Account successfully deleted from Keycloak." or error message

### Accessing the Logs Page

1. **Navigate to Admin**: Go to WordPress Admin → Monikit → Logs
2. **View Statistics**: See overview statistics at the top of the page
3. **Filter Logs**: Use the filtering options to find specific entries
4. **View Details**: Click "View" to see detailed information about any log entry
5. **Export Data**: Use "Export CSV" to download logs for compliance reporting

### Filtering Options

#### Email Filter
- **Usage**: Filter logs by specific email addresses
- **Example**: `user@example.com`
- **Features**: Partial matching supported

#### Action Filter
- **Options**:
  - Deletion Request (initial submission)
  - Email Confirmation (code verification)
  - Keycloak Deletion (actual account deletion)
  - Error
  - Expired Request

#### Status Filter
- **Options**:
  - Success (green)
  - Failed (red)
  - Pending (yellow)

#### Date Range Filter
- **From Date**: Start date for filtering
- **To Date**: End date for filtering
- **Format**: YYYY-MM-DD

### Statistics Dashboard

The statistics dashboard provides an overview of deletion activities:

#### Total Requests
- **Description**: Total number of deletion requests
- **Display**: Large number in the center

#### Successful Deletions
- **Description**: Successfully completed Keycloak deletions
- **Color**: Green
- **Use Case**: Track successful compliance and actual account removal

#### Failed Deletions
- **Description**: Failed deletion attempts
- **Color**: Red
- **Use Case**: Identify and resolve issues

#### Pending Requests
- **Description**: Requests submitted but awaiting user confirmation
- **Color**: Yellow
- **Use Case**: Monitor incomplete requests and track conversion rates

## 📊 Export Functionality

### CSV Export

#### Features
- **Filtered Export**: Export only filtered results
- **Complete Data**: All log fields included
- **Formatted Headers**: Clear column headers
- **Date Stamping**: Automatic filename with date

#### Export Format
```csv
ID,Email,Action,Status,Message,IP Address,Keycloak User ID,Keycloak Realm,Created At
1,user@example.com,deletion,success,Account deleted successfully,192.168.1.1,abc123,Patient,2024-01-15 10:30:00
```

#### Usage
1. **Apply Filters**: Set desired filters
2. **Click Export**: Click "Export CSV" button
3. **Download**: File downloads automatically
4. **Filename**: `monikit-deletion-logs-YYYY-MM-DD.csv`

## 🧹 Cleanup Functionality

### Bulk Deletion

#### Features
- **Individual Selection**: Checkbox for each log entry
- **Bulk Selection**: Select all/deselect all functionality
- **Single Deletion**: Delete individual log entries
- **Bulk Deletion**: Delete multiple selected entries at once
- **Smart Interface**: Dynamic button states and selection counters
- **Safe Operation**: Confirmation dialogs for all deletions

#### Usage
1. **Select Logs**: Use checkboxes to select individual logs or "Select All"
2. **Review Selection**: See count of selected items
3. **Delete Selected**: Click "Delete Selected" button
4. **Confirm Action**: Confirm the deletion operation
5. **Wait for Completion**: System processes the deletion
6. **Page Refresh**: Page refreshes to show updated data

#### Individual Deletion
- **Quick Delete**: Each log row has a "Delete" button
- **Immediate Feedback**: Row fades out on successful deletion
- **No Page Refresh**: Individual deletions update the interface dynamically

### Configurable Cleanup

#### Features
- **Flexible Retention**: Choose from 30 days to 3 years
- **Safe Operation**: Confirmation dialog with selected period
- **Bulk Operation**: Efficient bulk deletion
- **Progress Feedback**: Real-time status updates
- **User-Friendly Interface**: Dropdown selection with clear options

#### Available Retention Periods
- **30 days**: For frequent cleanup
- **3 months**: For quarterly cleanup
- **6 months**: For semi-annual cleanup
- **1 year**: Default GDPR compliance period
- **2 years**: Extended retention
- **3 years**: Long-term retention

#### Usage
1. **Select Period**: Choose retention period from dropdown
2. **Click Cleanup**: Click "Cleanup" button
3. **Confirm Action**: Confirm with selected period
4. **Wait for Completion**: System processes the cleanup
5. **Page Refresh**: Page refreshes to show updated data

#### Retention Policy
- **Default Period**: 365 days (1 year)
- **Configurable**: User-selectable from 30 days to 3 years
- **Compliance**: Meets GDPR retention requirements
- **Safety**: Clear warning about permanent deletion

## 🔍 Log Details View

### Detailed Information Display

#### Basic Information
- **Log ID**: Unique identifier
- **Email Address**: User's email
- **Action Type**: Human-readable action description
- **Status**: Color-coded status badge
- **Message**: Detailed message or error description

#### Technical Information
- **IP Address**: Client IP address
- **User Agent**: Browser/client information
- **Keycloak User ID**: Keycloak system user ID
- **Keycloak Realm**: Keycloak realm name
- **Timestamp**: Precise creation time

#### Request/Response Data
- **Request Data**: JSON-formatted request information
- **Response Data**: JSON-formatted response information
- **Formatted Display**: Pretty-printed JSON for readability

## 🔌 API Integration

### Programmatic Access

#### Get Logs
```php
// Get all logs
$logs_data = MONIGPDR()->logs->get_logs();

// Get filtered logs
$logs_data = MONIGPDR()->logs->get_logs(array(
    'email' => 'user@example.com',
    'action' => 'deletion',
    'status' => 'success',
    'date_from' => '2024-01-01',
    'date_to' => '2024-01-31'
));
```

#### Get Statistics
```php
// Get monthly statistics
$stats = MONIGPDR()->logs->get_statistics('month');

// Get yearly statistics
$stats = MONIGPDR()->logs->get_statistics('year');
```

#### Export Data
```php
// Export logs to CSV
$csv = MONIGPDR()->logs->export_csv(array(
    'email' => 'user@example.com'
));
```

#### Cleanup Logs
```php
// Cleanup logs older than 365 days
$deleted_count = MONIGPDR()->logs->cleanup_old_logs(365);
```

## 🛡️ Security Features

### Data Protection
- **Input Sanitization**: All inputs are properly sanitized
- **SQL Injection Protection**: Prepared statements used
- **XSS Prevention**: Output properly escaped
- **Access Control**: Only administrators can access logs

### Privacy Compliance
- **GDPR Compliance**: Meets GDPR audit trail requirements
- **Data Minimization**: Only necessary data is logged
- **Retention Limits**: Automatic cleanup of old data
- **Access Logging**: Tracks who accessed the logs

## 📈 Compliance Benefits

### GDPR Compliance
- **Audit Trail**: Complete record of deletion activities
- **Data Subject Rights**: Evidence of right to erasure compliance
- **Accountability**: Demonstrates compliance measures
- **Documentation**: Provides required documentation

### Regulatory Requirements
- **Data Protection**: Meets data protection regulations
- **Audit Requirements**: Provides audit evidence
- **Incident Response**: Supports incident investigation
- **Reporting**: Enables compliance reporting

## 🐛 Troubleshooting

### Common Issues

#### Logs Not Appearing
1. **Check Permissions**: Ensure user has `manage_options` capability
2. **Database Table**: Verify logs table exists
3. **Plugin Activation**: Ensure plugin is properly activated

#### Export Fails
1. **Memory Limits**: Check PHP memory limits
2. **File Permissions**: Verify write permissions
3. **Large Datasets**: Consider filtering before export

#### Cleanup Issues
1. **Database Permissions**: Check database write permissions
2. **Large Operations**: May take time for large datasets
3. **Transaction Limits**: Check database transaction limits

### Performance Optimization

#### Database Indexes
- **Email Index**: Optimizes email-based queries
- **Action Index**: Optimizes action-based filtering
- **Status Index**: Optimizes status-based filtering
- **Date Index**: Optimizes date range queries

#### Query Optimization
- **Pagination**: Efficient handling of large datasets
- **Filtering**: Optimized WHERE clauses
- **Indexing**: Proper use of database indexes

## 📝 Best Practices

### Log Management
1. **Regular Review**: Review logs regularly for anomalies
2. **Export Backups**: Export logs periodically for backup
3. **Cleanup Schedule**: Set up regular cleanup schedules
4. **Access Control**: Limit access to authorized personnel

### Compliance
1. **Retention Policy**: Establish clear retention policies
2. **Access Logging**: Log access to the logs themselves
3. **Data Protection**: Protect log data appropriately
4. **Regular Audits**: Conduct regular compliance audits

### Performance
1. **Index Maintenance**: Maintain database indexes
2. **Regular Cleanup**: Clean up old logs regularly
3. **Monitoring**: Monitor log table size
4. **Optimization**: Optimize queries for large datasets

## 🔄 Future Enhancements

### Planned Features
- **Real-time Notifications**: Email alerts for failed deletions
- **Advanced Analytics**: More detailed statistical analysis
- **API Endpoints**: REST API for external integrations
- **Custom Retention**: Configurable retention periods
- **Log Archiving**: Automatic archiving of old logs

### Integration Possibilities
- **SIEM Integration**: Security information and event management
- **Compliance Tools**: Integration with compliance platforms
- **Reporting Tools**: Advanced reporting capabilities
- **Monitoring Systems**: Integration with monitoring platforms

## 📞 Support

For support and questions about the logging feature:

1. **Check Documentation**: Review this README and other documentation
2. **Test Functionality**: Test in a staging environment
3. **Check Logs**: Review WordPress error logs for issues
4. **Contact Support**: Open an issue in the GitHub repository

## 📄 License

This logging feature is part of the Monikit GDPR User Data Deletion plugin and is licensed under the GPL v2 license. 