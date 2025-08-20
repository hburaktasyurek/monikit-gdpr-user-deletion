# Testing Contact Realm Deletion

This directory contains a test script for verifying the Contact realm user deletion functionality.

## Test File

### `test_contact_final.php`
Comprehensive test script for the Contact deletion API endpoint.

**Before running the test:**

1. **Update Test Credentials** (lines 16-19):
   ```php
   $username = 'your_username';
   $email = 'your_email@example.com';  
   $password = 'your_password';
   ```

2. **Update Plugin Settings** (lines 21-25) with values from WordPress Admin → Monikit Settings:
   ```php
   $keycloak_base_url = 'https://your-keycloak-server.com/';
   $contact_realm = 'Contact';
   $client_id = 'admin-cli';
   $contact_client_secret = 'your_contact_client_secret_here';
   ```

**To run the test:**
```bash
php test_contact_final.php
```

**What the test does:**
1. ✅ Obtains Contact user access token from Keycloak
2. ✅ Tests `/wp-json/monigpdr/v1/delete/contact` endpoint
3. ✅ Provides detailed success/failure feedback
4. ✅ Shows step-by-step execution results

**Expected Success Output:**
```
🎉 SUCCESS! Contact user deletion completed!
✅ Status: deleted
🎯 Test Result: PASSED
```

## API Endpoints

The plugin now supports two separate deletion endpoints:

| Endpoint | Purpose | Realm | Client Secret |
|----------|---------|-------|---------------|
| `/wp-json/monigpdr/v1/delete` | Patient user deletion | Patient | `keycloak_client_secret` |
| `/wp-json/monigpdr/v1/delete/contact` | Contact user deletion | Contact | `keycloak_contact_client_secret` |

Both endpoints require:
- `POST` request
- `Authorization: Bearer {access_token}` header
- Valid access token from respective Keycloak realm

## Troubleshooting

If tests fail, check:
1. **Settings Configuration**: WordPress Admin → Monikit Settings
2. **Keycloak Realm**: User exists in specified realm
3. **Client Secret**: Matches Keycloak client configuration
4. **User Credentials**: Valid for the specified realm
5. **Admin Permissions**: Admin user can delete users in Keycloak

See `CLAUDE.md` for detailed debugging instructions.