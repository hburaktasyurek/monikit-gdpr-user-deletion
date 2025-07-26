# Monikit GDPR User Data Deletion - Admin Settings Page

## 📋 Overview

This documentation describes the features and usage of the admin settings page for the Monikit GDPR User Data Deletion plugin.

## 🚀 Features

### ✅ Completed Features

#### 🔐 **Keycloak Connection Settings**
- **Keycloak Base URL**: Keycloak server address *(Required - Auto-formatted)*
- **Realm**: Keycloak realm name *(Required)*
- **Client ID**: Keycloak client ID *(Required)*
- **Client Secret**: Keycloak client secret (password field) *(Optional)*
- **Admin Username**: Keycloak admin API username *(Required)*
- **Admin Password**: Keycloak admin API password (password field) *(Required)*

##### URL Auto-Formatting
The Keycloak Base URL field automatically normalizes your input to ensure proper formatting:
- **Protocol**: Automatically adds `https://` if missing
- **Trailing Slash**: Ensures the URL ends with a single `/`
- **Flexible Path**: Works with or without `/auth/` in the URL
- **Examples**:
  - `keycloak.example.com` → `https://keycloak.example.com/`
  - `https://keycloak.example.com` → `https://keycloak.example.com/`
  - `https://keycloak.example.com/auth` → `https://keycloak.example.com/auth/`
  - `https://keycloak.example.com/auth/` → `https://keycloak.example.com/auth/`

#### 📧 **Email Templates (Multi-language)**
- **English Email Template**: Subject and HTML body
- **German Email Template**: Subject and HTML body
- **WYSIWYG Editor**: Rich text editor for HTML email templates
- **Placeholder Support**: `{user_email}`, `{confirmation_link}`, `{confirmation_code}`

#### 🌐 **Language Settings**
- **Default Language**: English or German selection

#### 🛡️ **Security Features**
- **Nonce Protection**: WordPress nonce for form security
- **Sanitization**: All input data sanitization
- **Permission Control**: Only users with `manage_options` capability
- **AJAX Security**: Nonce verification for AJAX requests
- **Required Field Validation**: Server-side and client-side validation

#### 🎨 **User Experience**
- **Modern UI**: WordPress admin theme compatible design
- **Responsive Design**: Usable on mobile devices
- **Form Validation**: Real-time validation with JavaScript
- **Password Visibility Toggle**: Show/hide button for password fields
- **Email Preview**: Preview feature to test templates
- **Keycloak Connection Test**: Real token endpoint testing with access token validation
- **Required Field Indicators**: Red asterisks (*) for mandatory fields

## 📁 File Structure

```
monikit-gdpr-user-deletion/
├── core/
│   ├── class-monikit-app-gdpr-user-data-deletion.php (Main class - updated)
│   └── includes/
│       ├── classes/
│       │   └── class-monikit-app-gdpr-user-data-deletion-admin.php (NEW - Admin class)
│       └── assets/
│           ├── css/
│           │   └── admin-styles.css (NEW - Admin CSS)
│           └── js/
│               └── admin-scripts.js (NEW - Admin JavaScript)
├── test-admin-page.php (NEW - Test file)
└── ADMIN_SETTINGS_README.md (NEW - This file)
```

## 🔧 Installation and Usage

### 1. Plugin Activation
Activate the plugin in the WordPress admin panel.

### 2. Access Admin Menu
- The "Monikit" menu will appear in the left sidebar of the WordPress admin panel
- Click on the menu to access the settings page

### 3. Configure Settings

#### Keycloak Settings (Required Fields)
1. **Keycloak Base URL**: `https://your-keycloak-server.com/auth/` *(Required)*
2. **Realm**: Your Keycloak realm name (e.g., `Patient`) *(Required)*
3. **Client ID**: Your Keycloak client ID (e.g., `admin-cli`) *(Required)*
4. **Client Secret**: Your client secret if required *(Optional)*
5. **Admin Username**: Your Keycloak admin API username *(Required)*
6. **Admin Password**: Your Keycloak admin API password *(Required)*

#### Email Templates
1. **English Email**: Fill in subject and HTML body
2. **German Email**: Fill in subject and HTML body
3. **Use Placeholders**:
   - `{user_email}`: User's email address
   - `{confirmation_link}`: Clickable verification link
   - `{confirmation_code}`: Verification code

#### Language Settings
1. **Default Language**: Select English or German

### 4. Test and Validate
- **🔐 Test Keycloak Connection**: Test your Keycloak connection by attempting to retrieve an access token
- **📧 Preview Email**: Preview email templates
- **Save Settings**: Save your settings

## 🔌 API Usage

### Getting Settings Programmatically

```php
// Get all settings
$settings = MONIGPDR()->admin->get_settings();

// Get a specific setting
$keycloak_url = MONIGPDR()->admin->get_settings('keycloak_base_url');

// Get email template
$email_template = MONIGPDR()->admin->get_email_template('en', 'html');
```

### Email Template Override

```php
// Override email template with filter
add_filter('monikit_email_template_en', function($template, $type) {
    if ($type === 'html') {
        return 'Custom email template content';
    }
    return $template;
}, 10, 2);
```

## 🎯 Feature Details

### Email Template Placeholders

| Placeholder | Description | Required |
|-------------|-------------|----------|
| `{user_email}` | User's email address | No |
| `{confirmation_link}` | Clickable verification link | **Yes** |
| `{confirmation_code}` | Verification code | **Yes** |

### Required Fields

The following fields are **mandatory** and must be filled in before saving:

- **Keycloak Base URL**: The base URL of your Keycloak server
- **Realm**: The Keycloak realm name
- **Client ID**: The Keycloak client ID
- **Admin Username**: The Keycloak admin API username
- **Admin Password**: The Keycloak admin API password

These fields are marked with a red asterisk (*) in the admin interface and will show validation errors if left empty.

### Default Email Templates

When the plugin is first installed, professional email templates are automatically created for both English and German.

### Keycloak Connection Testing

The plugin includes a comprehensive two-step Keycloak connection test that validates your configuration:

#### Step 1: Token Retrieval
1. **Token Endpoint Testing**: Attempts to retrieve an access token from Keycloak's token endpoint
2. **Credential Validation**: Verifies that admin username/password are correct
3. **Client Configuration**: Checks that the client ID and secret (if provided) are valid
4. **Network Connectivity**: Ensures the Keycloak server is reachable

#### Step 2: Realm Access Verification
1. **User List Retrieval**: Uses the access token to fetch users from the specified realm
2. **Permission Validation**: Verifies that the client has proper admin permissions
3. **Realm Existence**: Confirms the realm exists and is accessible

#### Test Process:
- **Token Endpoint**: `{KEYCLOAK_BASE_URL}/auth/realms/master/protocol/openid-connect/token`
- **Users Endpoint**: `{KEYCLOAK_BASE_URL}/auth/admin/realms/{REALM}/users`
- **Token Method**: POST with `application/x-www-form-urlencoded` content type
- **Users Method**: GET with `Authorization: Bearer {access_token}` header
- **Success Criteria**: Both token retrieval and user list access succeed

#### Error Handling:
- **Connection Errors**: Network connectivity issues
- **Authentication Errors**: Invalid credentials or client configuration
- **Permission Errors**: Valid token but insufficient permissions (401/403)
- **Realm Errors**: Realm not found or inaccessible (404)
- **Server Errors**: Keycloak server issues or configuration problems

### Security Measures

- All form data is sanitized using `sanitize_text_field()` and `wp_kses_post()`
- AJAX requests are protected with nonces
- Only users with `manage_options` capability can access
- Password fields are handled securely
- Required field validation on both client and server side

## 🐛 Troubleshooting

### Admin Page Not Visible
1. Ensure the plugin is activated
2. Check that you have `manage_options` capability
3. Enable WordPress debug mode and check for error messages

### Email Templates Not Loading
1. Ensure the WYSIWYG editor is loaded
2. Check for JavaScript errors
3. Check browser console

### Keycloak Connection Test Fails
1. Check that your Keycloak server is accessible
2. Ensure the URL format is correct (e.g., `https://your-keycloak-server.com/auth/`)
3. Verify that the admin credentials are correct
4. Check that the client ID exists and is configured properly
5. Ensure the client secret is correct (if required)
6. Check firewall settings and network connectivity
7. Verify that the Keycloak server allows password grant type for the client

### Token Valid but Realm Access Fails
1. **401/403 Errors**: Check that the client has admin permissions in Keycloak
2. **404 Errors**: Verify the realm name is correct and exists
3. **Permission Issues**: Ensure the client has the following roles:
   - `view-users` role for the realm
   - `query-users` role for the realm
   - Or `realm-admin` role for full access
4. **Client Configuration**: Verify the client is configured as a confidential client with proper service account roles

### Required Field Validation Errors
1. Fill in all fields marked with red asterisks (*)
2. Ensure no required fields are left empty
3. Check that the form validation is working properly

## 📝 Developer Notes

### Adding New Features

1. **Adding New Setting Field**:
   ```php
   // Add new field in init_settings() method
   add_settings_field(
       'new_field_key',
       'New Field Label',
       array( $this, 'render_field' ),
       'monikit_settings',
       'section_name',
       array(
           'field_key' => 'new_field_key',
           'field_type' => 'text',
           'required' => false, // Set to true if mandatory
           'description' => 'Field description'
       )
   );
   ```

2. **Adding New Section**:
   ```php
   add_settings_section(
       'new_section',
       'New Section Title',
       array( $this, 'new_section_callback' ),
       'monikit_settings'
   );
   ```

### CSS Customization

You can customize the appearance of the admin page by editing the `admin-styles.css` file.

### JavaScript Customization

You can add additional JavaScript functionality by editing the `admin-scripts.js` file.

## 📞 Support

If you encounter any issues or have feature requests, please open an issue in the GitHub repository.

## 📄 License

This plugin is licensed under the GPL v2 license. 