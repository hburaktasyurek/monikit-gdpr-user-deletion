# Changelog

## Version 1.3.0 - Contact Realm Support

### 🆕 New Features

**Dual-Realm Support**
- Added Contact Realm field to WordPress admin settings
- Added Contact Client Secret field (separate from Patient)
- New API endpoint: `/wp-json/monigpdr/v1/delete/contact`
- Independent authentication for Patient and Contact realms

**Contact User Deletion API**
- OAuth2 Bearer token authentication for Contact realm
- Token validation against Contact realm userinfo endpoint
- Contact user deletion using admin credentials
- Separate logging for Contact vs Patient deletions

### 🔧 Core Changes

**Admin Settings (class-monikit-app-gdpr-user-data-deletion-admin.php)**
- Added `keycloak_contact_realm` setting field
- Added `keycloak_contact_client_secret` setting field  
- Updated field rendering to handle password fields
- Added Contact fields to sanitization array

**API Endpoints (class-monikit-app-gdpr-user-data-deletion-api.php)**
- Registered `/delete/contact` REST route
- Added `handle_contact_deletion_request()` method
- Added `validate_contact_access_token()` method
- Added `delete_contact_user_by_token_info()` method
- Maintained consistent error handling and logging patterns

**Plugin Version (monikit-app-gdpr-user-data-deletion.php)**
- Updated version to 1.3.0

### 📚 Documentation Updates

**API_README.md**
- Added Contact deletion endpoint documentation
- Updated examples with both Patient and Contact usage
- Added separate authentication sections for each realm

**API_TEST.md** 
- Complete rewrite for OAuth2 Bearer token system
- Removed outdated API key references
- Added comprehensive test cases for both endpoints
- Added error code reference table

**New Documentation Files**
- `CLAUDE.md` - Developer guidance for future Claude instances
- `TEST_README.md` - Testing instructions and setup guide
- `CHANGELOG.md` - Version history and changes

### 🧪 Testing

**test_contact_final.php**
- Comprehensive test script for Contact deletion
- Step-by-step token acquisition and API testing
- Detailed success/failure feedback
- Template for easy configuration

### 🔒 Security & Compliance

- ✅ Separate client secrets for realm isolation
- ✅ OAuth2 Bearer token validation maintained
- ✅ Rate limiting applied to all endpoints
- ✅ Comprehensive audit logging
- ✅ No breaking changes to existing Patient functionality

### 🎯 API Endpoints Summary

| Endpoint | Purpose | Realm | Client Secret |
|----------|---------|-------|---------------|
| `/wp-json/monigpdr/v1/delete` | Patient deletion | Patient | `keycloak_client_secret` |
| `/wp-json/monigpdr/v1/delete/contact` | Contact deletion | Contact | `keycloak_contact_client_secret` |

### 🔄 Migration Notes

- **No breaking changes**: Existing Patient functionality unchanged
- **New settings required**: Contact Realm and Contact Client Secret must be configured
- **Backward compatible**: All existing integrations continue to work
- **Independent operation**: Patient and Contact deletions work separately

---

## Version 1.2.0 - OAuth2 Access Tokens (Previous)

- Switched from API keys to OAuth2 Bearer tokens
- Added Keycloak integration for user authentication
- Implemented secure token validation
- Added comprehensive API documentation