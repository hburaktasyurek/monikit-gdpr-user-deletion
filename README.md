# Monikit GDPR User Data Deletion

[![WordPress Plugin](https://img.shields.io/badge/WordPress-Plugin-blue.svg)](https://wordpress.org/)
[![License](https://img.shields.io/badge/License-GPL%20v2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Version-1.0.0-orange.svg)](https://github.com/hburaktasyurek/monikit-gdpr-user-deletion)

A comprehensive WordPress plugin that enables GDPR-compliant user account deletion with secure Keycloak API integration. This plugin provides a complete solution for handling user data deletion requests in accordance with GDPR requirements.

## 🚀 Features

### 🔐 **Keycloak Integration**
- **Secure API Connection**: Direct integration with Keycloak authentication server
- **User Account Deletion**: Automated deletion of user accounts from Keycloak
- **Realm Management**: Support for multiple Keycloak realms
- **Admin API Access**: Secure admin-level API operations
- **Connection Testing**: Built-in connection validation and testing

### 📧 **Email Confirmation System**
- **Multi-language Support**: English and German email templates
- **Professional Templates**: Pre-configured, GDPR-compliant email templates
- **Verification Codes**: 6-digit security codes for confirmation
- **Template Customization**: WYSIWYG editor for easy template modification
- **Placeholder Support**: Dynamic content insertion (`{user_email}`, `{confirmation_link}`, `{confirmation_code}`)

### 🌐 **Public Deletion Form**
- **Shortcode Support**: `[monigpdr_deletion_form]` for easy embedding
- **Multiple Styles**: Default, minimal, and card styling options
- **Responsive Design**: Mobile-friendly interface
- **Customizable**: Title, subtitle, and appearance options
- **AJAX Processing**: Seamless user experience without page reloads

### 🔌 **OAuth2 REST API**
- **Secure API Endpoint**: `POST /wp-json/monigpdr/v1/delete` for mobile app integration
- **OAuth2 Authentication**: Uses Keycloak access tokens for secure authentication
- **Token Validation**: Validates tokens via Keycloak userinfo endpoint
- **User Identification**: Automatically identifies users from token claims
- **Mobile App Ready**: Perfect for iOS and Android app integration

### 📊 **Comprehensive Logging**
- **Audit Trail**: Complete logging of all deletion activities
- **Status Tracking**: Request, confirmation, and deletion status monitoring
- **Admin Dashboard**: Statistics and detailed log viewing
- **CSV Export**: Compliance reporting capabilities
- **Automatic Cleanup**: Configurable log retention policies

### 🛡️ **Security Features**
- **CSRF Protection**: WordPress nonce verification
- **Input Sanitization**: All data properly sanitized and validated
- **Rate Limiting**: Protection against abuse and spam
- **Permission Control**: Admin-only access to sensitive features
- **Secure API Calls**: Encrypted communication with Keycloak

### 🌍 **Multi-language Support**
- **Built-in Translations**: English and German language support
- **Translation Management**: Admin interface for managing translations
- **Automatic Detection**: Language detection for forms and emails
- **Extensible**: Support for additional languages

## 📋 Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **Keycloak Server**: Accessible Keycloak authentication server
- **Admin Permissions**: WordPress admin access for configuration

## 🔧 Installation

### Method 1: Manual Installation
1. Download the plugin files
2. Upload to `/wp-content/plugins/monikit-gdpr-user-deletion/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Navigate to **Monikit → Settings** to configure

### Method 2: Git Clone
```bash
cd wp-content/plugins/
git clone https://github.com/hburaktasyurek/monikit-gdpr-user-deletion.git
cd monikit-gdpr-user-deletion
composer install
```

## ⚙️ Configuration

### 1. Keycloak Settings
Navigate to **WordPress Admin → Monikit → Settings** and configure:

- **Keycloak Base URL**: Your Keycloak server URL (e.g., `https://keycloak.example.com/auth/`)
- **Realm**: Your Keycloak realm name
- **Client ID**: Your Keycloak client ID
- **Client Secret**: Your client secret (if required)
- **Admin Username**: Keycloak admin API username
- **Admin Password**: Keycloak admin API password

### 2. Email Templates
Configure professional email templates for both English and German:

- **Subject Lines**: Customizable email subjects
- **HTML Content**: Rich HTML email templates with placeholders
- **Preview Function**: Test templates before saving

### 3. Public Form Settings
- **Enable Public Deletion**: Toggle form functionality
- **Default Language**: Set default language for forms
- **Form Styling**: Choose from multiple style options

## 📖 Usage

### Shortcode Implementation

#### Basic Usage
```
[monigpdr_deletion_form]
```

#### Custom Configuration
```
[monigpdr_deletion_form 
    title="Delete My Account" 
    subtitle="Request permanent deletion of your data"
    style="card"
    show_title="true"
    show_subtitle="true"
]
```

#### Style Options
- **Default**: Full styling with gradient background
- **Minimal**: Clean, minimal appearance
- **Card**: Card-like design with subtle shadow

### Programmatic Usage

#### Check Settings
```php
// Get all settings
$settings = MONIGPDR()->admin->get_settings();

// Get specific setting
$keycloak_url = MONIGPDR()->admin->get_settings('keycloak_base_url');
```

#### Check Form Status
```php
// Check if public deletion is enabled
$is_enabled = MONIGPDR()->helpers->is_public_deletion_enabled();

// Use in conditional logic
if ($is_enabled) {
    echo do_shortcode('[monigpdr_deletion_form]');
}
```

#### Access Logs
```php
// Get logs with filters
$logs = MONIGPDR()->logs->get_logs(array(
    'email' => 'user@example.com',
    'action' => 'deletion',
    'status' => 'success'
));

// Get statistics
$stats = MONIGPDR()->logs->get_statistics('month');
```

### OAuth2 API Usage

#### Mobile App Integration
The API is designed for mobile applications requiring account deletion functionality:

1. **Configure Keycloak** settings in the plugin
2. **Implement OAuth2 flow** to obtain access tokens from Keycloak
3. **Use the endpoint** with Bearer token authentication

#### API Endpoint
```
POST /wp-json/monigpdr/v1/delete
```

#### Example Request
```bash
curl -X POST "https://your-domain.com/wp-json/monigpdr/v1/delete" \
  -H "Authorization: Bearer your_access_token" \
  -H "Content-Type: application/json"
```

#### Example Response
```json
{
    "status": "deleted"
}
```

Perfect for iOS and Android apps. For complete API documentation, see [API_README.md](API_README.md).

## 📁 File Structure

```
monikit-gdpr-user-deletion/
├── monikit-app-gdpr-user-data-deletion.php    # Main plugin file
├── core/
│   ├── class-monikit-app-gdpr-user-data-deletion.php
│   └── includes/
│       ├── classes/
│       │   ├── class-monikit-app-gdpr-user-data-deletion-admin.php
│       │   ├── class-monikit-app-gdpr-user-data-deletion-api.php
│       │   ├── class-monikit-app-gdpr-user-data-deletion-public.php
│       │   ├── class-monikit-app-gdpr-user-data-deletion-helpers.php
│       │   ├── class-monikit-app-gdpr-user-data-deletion-logs.php
│       │   ├── class-monikit-app-gdpr-user-data-deletion-settings.php
│       │   └── class-monikit-app-gdpr-user-data-deletion-run.php
│       ├── assets/
│       │   ├── css/
│       │   │   ├── admin-styles.css
│       │   │   └── public-styles.css
│       │   └── js/
│       │       ├── admin-scripts.js
│       │       └── public-scripts.js
│       ├── templates/
│       │   └── deletion-form.php
│       └── integrations/
│           └── demo-integration/
├── languages/                                  # Translation files
├── vendor/                                     # Composer dependencies
├── README.md                                   # This file
├── API_README.md                               # API documentation
├── ADMIN_SETTINGS_README.md                    # Admin settings documentation
├── SHORTCODE_USAGE.md                          # Shortcode documentation
└── LOGS_FEATURE_README.md                      # Logging feature documentation
```

## 🔍 Admin Interface

### Settings Page
- **Keycloak Configuration**: Connection settings and testing
- **Email Templates**: Multi-language email template management
- **Language Settings**: Default language configuration
- **Public Form Settings**: Form enablement and styling options

### Logs Page
- **Statistics Dashboard**: Overview of deletion activities
- **Filtered Log View**: Search and filter log entries
- **Detailed Log View**: Complete information for each log entry
- **Export Functionality**: CSV export for compliance reporting
- **Cleanup Tools**: Automatic and manual log cleanup

### Translation Page
- **String Management**: Edit translatable strings
- **Language Support**: English and German translations
- **Real-time Editing**: Direct editing in admin interface

## 🛡️ Security & Compliance

### GDPR Compliance
- **Right to Erasure**: Complete account deletion functionality
- **Audit Trail**: Comprehensive logging of all deletion activities
- **Data Minimization**: Only necessary data is processed
- **Transparency**: Clear information about data processing
- **Security**: Secure handling of sensitive operations

### Security Measures
- **CSRF Protection**: WordPress nonce verification
- **Input Validation**: Comprehensive input sanitization
- **API Security**: Secure Keycloak API communication
- **Access Control**: Role-based access control
- **Rate Limiting**: Protection against abuse

## 🐛 Troubleshooting

### Common Issues

#### Keycloak Connection Fails
1. Verify Keycloak server is accessible
2. Check URL format (should end with `/auth/`)
3. Ensure admin credentials are correct
4. Verify client configuration in Keycloak
5. Check firewall and network settings

#### Email Templates Not Loading
1. Ensure WYSIWYG editor is loaded
2. Check for JavaScript errors in browser console
3. Verify WordPress version compatibility
4. Clear browser cache

#### Shortcode Not Working
1. Enable public deletion in settings
2. Verify shortcode syntax
3. Clear caching plugins
4. Test with default WordPress theme

#### Logs Not Appearing
1. Check user permissions (`manage_options` capability)
2. Verify database table exists
3. Ensure plugin is properly activated
4. Check WordPress error logs

### Debug Mode
Enable WordPress debug mode for detailed error information:
```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

## 📈 Performance

### Optimization Features
- **Database Indexing**: Optimized queries with proper indexes
- **Pagination**: Efficient handling of large datasets
- **Caching**: Smart caching of configuration data
- **AJAX Processing**: Asynchronous form processing
- **Resource Loading**: Optimized CSS and JavaScript loading

### Best Practices
- **Regular Cleanup**: Set up automatic log cleanup
- **Monitoring**: Monitor log table size and performance
- **Backup**: Regular backup of configuration and logs
- **Testing**: Test in staging environment before production

## 🔄 Development

### Hooks and Filters
```php
// Plugin loaded hook
add_action('MONIGPDR/plugin_loaded', function() {
    // Custom initialization code
});

// Email template filter
add_filter('monikit_email_template_en', function($template, $type) {
    // Customize email template
    return $template;
}, 10, 2);

// Settings filter
add_filter('monikit_settings', function($settings) {
    // Modify settings
    return $settings;
});
```

### Adding Custom Features
1. **New Settings**: Add fields to the settings page
2. **Custom Logging**: Extend the logging system
3. **Additional Styles**: Create custom form styles
4. **Integration**: Add support for additional systems

## 📞 Support

### Documentation
- **[Admin Settings](ADMIN_SETTINGS_README.md)**: Detailed admin interface documentation
- **[Shortcode Usage](SHORTCODE_USAGE.md)**: Complete shortcode documentation
- **[Logging Feature](LOGS_FEATURE_README.md)**: Comprehensive logging documentation

### Getting Help
1. **Check Documentation**: Review all README files
2. **Test in Staging**: Always test in staging environment first
3. **Enable Debug Mode**: Use WordPress debug mode for troubleshooting
4. **Check Logs**: Review WordPress error logs and plugin logs
5. **GitHub Issues**: Open an issue in the GitHub repository

### Contributing
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📄 License

This plugin is licensed under the **GPL v2** license. See the [license.txt](license.txt) file for details.

## 👨‍💻 Author

**Hasan Burak TASYUREK**
- **Website**: [https://hbglobal.dev](https://hbglobal.dev)
- **GitHub**: [https://github.com/hburaktasyurek](https://github.com/hburaktasyurek)

## 🔄 Version History

### Version 1.0.0
- Initial release
- Keycloak integration
- Email confirmation system
- Public deletion form with shortcode
- Comprehensive logging system
- Multi-language support (English/German)
- Admin settings interface
- Security features and GDPR compliance

---

**Note**: This plugin is designed for use with Keycloak authentication servers. Ensure your Keycloak server is properly configured and accessible before installation.