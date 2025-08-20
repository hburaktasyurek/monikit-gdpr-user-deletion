# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Plugin Overview

This is a WordPress plugin for GDPR-compliant user account deletion with Keycloak integration. The plugin provides:
- Public deletion form (shortcode-based)
- OAuth2 REST API for mobile apps
- Admin interface for settings and logs
- Multi-language support (English/German)
- Email confirmation system

## Key Commands

### Development
```bash
# Install dependencies
composer install

# Test Contact realm OAuth2 API (update credentials in test file first)
php test_contact_final.php

# Watch for WordPress debug logs
tail -f ../../debug.log
```

### Testing API Endpoints
```bash
# Patient deletion endpoint
curl -X POST "https://plugindev.local/wp-json/monigpdr/v1/delete" \
  -H "Authorization: Bearer {access_token}" \
  -H "Content-Type: application/json"

# Contact deletion endpoint  
curl -X POST "https://plugindev.local/wp-json/monigpdr/v1/delete/contact" \
  -H "Authorization: Bearer {access_token}" \
  -H "Content-Type: application/json"
```

## Architecture

### Plugin Structure
- **Entry Point**: `monikit-app-gdpr-user-data-deletion.php` - Main plugin file with constants
- **Core Class**: `core/class-monikit-app-gdpr-user-data-deletion.php` - Plugin initialization and dependency injection
- **Singleton Pattern**: Access all functionality via `MONIGPDR()` global function

### Core Components
1. **Admin** (`class-monikit-app-gdpr-user-data-deletion-admin.php`): Settings management, email templates, Keycloak configuration
2. **API** (`class-monikit-app-gdpr-user-data-deletion-api.php`): OAuth2 REST endpoints for mobile apps
3. **Public** (`class-monikit-app-gdpr-user-data-deletion-public.php`): Shortcode implementation, AJAX handlers
4. **Helpers** (`class-monikit-app-gdpr-user-data-deletion-helpers.php`): Keycloak API integration, utility functions
5. **Logs** (`class-monikit-app-gdpr-user-data-deletion-logs.php`): Deletion logging and audit trail
6. **Settings** (`class-monikit-app-gdpr-user-data-deletion-settings.php`): Settings page rendering

### Keycloak Integration
The plugin supports two separate Keycloak realms with independent authentication:
- **Patient Realm**: Regular users (setting: `keycloak_realm`)
- **Contact Realm**: Contact users (setting: `keycloak_contact_realm`)

Each realm has separate configuration:
- Base URL (e.g., `https://keycloak.example.com/auth/`)
- Realm name
- Client ID (shared between realms)
- **Separate client secrets**: `keycloak_client_secret` (Patient) and `keycloak_contact_client_secret` (Contact)
- Admin credentials for user deletion (shared)

### API Authentication Flow
1. Mobile app obtains OAuth2 access token from Keycloak
2. App sends POST request with Bearer token
3. Plugin validates token via Keycloak userinfo endpoint
4. Plugin identifies user from token claims (sub or email)
5. Plugin deletes user using admin credentials
6. Response returned with status

### Database Schema
Plugin creates custom table `wp_monikit_logs` with:
- `id` (bigint): Primary key
- `user_email` (varchar): User email address
- `action` (varchar): Action type
- `status` (varchar): Current status
- `source` (varchar): Request source (UI/API/access_token)
- `details` (text): JSON details
- `created_at` (datetime): Timestamp
- `ip_address` (varchar): Client IP
- `user_agent` (text): Browser/client info

## Key WordPress Hooks

### Actions
- `MONIGPDR/plugin_loaded`: Plugin initialization complete
- `wp_ajax_monikit_test_keycloak_connection`: Test Keycloak connectivity
- `wp_ajax_monikit_delete_user`: Process deletion request
- `wp_ajax_monikit_confirm_deletion`: Confirm deletion with code
- `wp_ajax_monikit_export_logs`: Export logs to CSV

### Filters
- `monikit_email_template_en`: Customize English email template
- `monikit_email_template_de`: Customize German email template
- `monikit_settings`: Modify plugin settings
- `monikit_deletion_form_html`: Customize deletion form HTML

### Shortcode
`[monigpdr_deletion_form]` - Display public deletion form

Parameters:
- `title`: Form title
- `subtitle`: Form subtitle  
- `style`: Form style (default/minimal/card)
- `show_title`: Show/hide title (true/false)
- `show_subtitle`: Show/hide subtitle (true/false)

## Important Considerations

### Security
- All user inputs are sanitized using WordPress functions (`sanitize_text_field`, `sanitize_email`, etc.)
- CSRF protection via WordPress nonces
- Rate limiting on API endpoints (10 requests per minute)
- Admin-only access to settings (`manage_options` capability)
- OAuth2 Bearer token validation for API requests

### Multi-language Support
- Translation files in `/languages/` directory
- Text domain: `monikit-app-gdpr-user-data-deletion`
- Admin interface for managing translations
- Dynamic language detection for forms and emails

### Error Handling
- Comprehensive error logging to `wp_monikit_logs` table
- WordPress debug logging via `error_log()` when `WP_DEBUG` is enabled
- Detailed error responses for API endpoints
- User-friendly error messages in UI

### Performance
- Database queries use prepared statements
- Log cleanup functionality to prevent table bloat
- AJAX processing for seamless UX
- Pagination for log viewing

## Common Development Tasks

### Adding New API Endpoint
1. Register route in `register_api_routes()` method in API class
2. Add handler method with Bearer token validation
3. Update API documentation in `API_README.md`
4. Add test case in `test_contact_final.php`

### Modifying Email Templates
1. Default templates in `get_default_templates()` method in Admin class
2. Templates stored in database as `monikit_settings` option
3. Use placeholders: `{user_email}`, `{confirmation_link}`, `{confirmation_code}`
4. Test with preview functionality in admin

### Adding New Settings Field
1. Add field in `admin_page()` method in Settings class
2. Add sanitization in `sanitize_settings()` method in Admin class
3. Update default values in plugin activation
4. Document in `ADMIN_SETTINGS_README.md`

### Debugging Keycloak Issues
1. Enable `WP_DEBUG` and `WP_DEBUG_LOG` in wp-config.php
2. Use "Test Keycloak Connection" button in admin
3. Check logs section for detailed error messages
4. Verify realm names and credentials
5. Test with `test_contact_final.php` script

## Local Development Setup

1. Place plugin in `/wp-content/plugins/monikit-gdpr-user-deletion/`
2. Run `composer install` for development dependencies
3. Activate plugin in WordPress admin
4. Configure Keycloak settings in Monikit → Settings
5. Test connection and set up email templates
6. Enable public deletion form if needed

## Testing Checklist

- [ ] Keycloak connection test passes
- [ ] Email templates preview correctly
- [ ] Public form submission works
- [ ] Confirmation code validates
- [ ] User deletion succeeds in Keycloak
- [ ] API endpoint returns correct status codes
- [ ] Logs are created for all actions
- [ ] Rate limiting prevents abuse
- [ ] Multi-language support works