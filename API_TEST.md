# Monikit GDPR API Testing Guide

This guide covers testing the OAuth2 Bearer token API endpoints for user deletion.

## API Endpoints

### Patient Deletion
`POST /wp-json/monigpdr/v1/delete`

### Contact Deletion  
`POST /wp-json/monigpdr/v1/delete/contact`

## Prerequisites

1. **Configure Keycloak Settings** in WordPress Admin → Monikit Settings:
   - Keycloak Base URL
   - Patient Realm & Client Secret
   - Contact Realm & Client Secret
   - Admin credentials

2. **Obtain OAuth2 Access Token** from Keycloak:
   ```bash
   curl -X POST "https://your-keycloak.com/auth/realms/Patient/protocol/openid-connect/token" \
     -H "Content-Type: application/x-www-form-urlencoded" \
     -d "grant_type=password" \
     -d "client_id=admin-cli" \
     -d "client_secret=your-patient-client-secret" \
     -d "username=user@example.com" \
     -d "password=user-password"
   ```

## Test Cases

### 1. Valid Patient Deletion Request
```bash
curl -X POST "https://your-site.com/wp-json/monigpdr/v1/delete" \
  -H "Authorization: Bearer {access_token}" \
  -H "Content-Type: application/json"
```

**Expected Response (200 OK):**
```json
{
  "status": "deleted"
}
```

### 2. Valid Contact Deletion Request
```bash
curl -X POST "https://your-site.com/wp-json/monigpdr/v1/delete/contact" \
  -H "Authorization: Bearer {contact_access_token}" \
  -H "Content-Type: application/json"
```

**Expected Response (200 OK):**
```json
{
  "status": "deleted"
}
```

### 3. Invalid/Missing Access Token
```bash
curl -X POST "https://your-site.com/wp-json/monigpdr/v1/delete" \
  -H "Authorization: Bearer invalid-token" \
  -H "Content-Type: application/json"
```

**Expected Response (401 Unauthorized):**
```json
{
  "error": "invalid_token"
}
```

### 4. Missing Authorization Header
```bash
curl -X POST "https://your-site.com/wp-json/monigpdr/v1/delete" \
  -H "Content-Type: application/json"
```

**Expected Response (401 Unauthorized):**
```json
{
  "error": "missing_token"
}
```

### 5. User Not Found in Keycloak
```bash
curl -X POST "https://your-site.com/wp-json/monigpdr/v1/delete" \
  -H "Authorization: Bearer {valid_token_for_nonexistent_user}" \
  -H "Content-Type: application/json"
```

**Expected Response (404 Not Found):**
```json
{
  "error": "user_not_found"
}
```

### 6. Keycloak Configuration Error
When admin credentials are incorrect or permissions insufficient:

**Expected Response (500 Internal Server Error):**
```json
{
  "error": "keycloak_error"
}
```

### 7. Rate Limiting (>10 requests/minute)
```bash
# After exceeding rate limit
curl -X POST "https://your-site.com/wp-json/monigpdr/v1/delete" \
  -H "Authorization: Bearer {access_token}" \
  -H "Content-Type: application/json"
```

**Expected Response (429 Too Many Requests):**
```json
{
  "error": "rate_limit_exceeded"
}
```

## Authentication Flow

1. **Get Access Token** from appropriate Keycloak realm:
   - **Patient**: Use Patient realm + `keycloak_client_secret`
   - **Contact**: Use Contact realm + `keycloak_contact_client_secret`

2. **Include Bearer Token** in API request:
   ```
   Authorization: Bearer {access_token}
   ```

3. **Plugin validates token** against Keycloak userinfo endpoint

4. **User deletion** performed using admin credentials

## Error Codes

| Error Code | HTTP Status | Description |
|------------|-------------|-------------|
| `missing_token` | 401 | No Authorization header provided |
| `invalid_token` | 401 | Token invalid or expired |
| `user_not_found` | 404 | User doesn't exist in Keycloak realm |
| `keycloak_error` | 500 | Admin credentials or permissions issue |
| `rate_limit_exceeded` | 429 | Too many requests (>10/minute) |

## Security Features

- ✅ OAuth2 Bearer token authentication
- ✅ Token validation against Keycloak
- ✅ Rate limiting (10 requests per minute)
- ✅ Separate realms with independent client secrets
- ✅ Comprehensive request logging
- ✅ No sensitive data in responses
- ✅ CORS headers for API security

## Logging

All API requests are logged in WordPress Admin → Monikit → Logs with:
- **Source**: "access_token" (OAuth2 requests)
- **User Email**: Extracted from token claims
- **IP Address**: Client IP tracking
- **User Agent**: Client identification
- **Status**: Success/failure with details
- **Realm**: Which realm was used for deletion

## Testing with Script

Use the provided test script for comprehensive testing:

```bash
# Update credentials in test_contact_final.php first
php test_contact_final.php
```

The script will:
1. ✅ Obtain access token from Keycloak
2. ✅ Test API endpoint with valid token
3. ✅ Provide detailed success/failure feedback
4. ✅ Show step-by-step execution results

See `TEST_README.md` for detailed testing instructions.