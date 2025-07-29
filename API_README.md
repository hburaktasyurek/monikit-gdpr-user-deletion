# Monikit GDPR API Documentation

## Overview

The Monikit GDPR plugin provides a secure REST API endpoint for programmatic user deletion using OAuth2 access tokens. This API is designed for mobile applications and external services that need to delete user accounts directly using Keycloak-issued access tokens.

## Features

- **OAuth2 Authentication**: Uses Keycloak access tokens for secure authentication
- **Token Validation**: Validates tokens via Keycloak userinfo endpoint
- **User Identification**: Automatically identifies users from token claims
- **Direct Deletion**: Deletes users directly from Keycloak without email confirmation
- **Unified Logging**: Consistent logging with UI-initiated deletions
- **Error Handling**: Comprehensive error responses and status codes
- **Security**: No API keys or credentials required in requests

## API Endpoint

```
POST /wp-json/monigpdr/v1/delete
```

### Base URL
```
https://your-domain.com/wp-json/monigpdr/v1/delete
```

## Authentication

The API uses OAuth2 Bearer tokens issued by Keycloak. The access token must be valid and contain user information (sub claim or email).

### Required Headers
```
Authorization: Bearer {access_token}
Content-Type: application/json
```

### Request Body
No body parameters required. The user is identified from the access token.

## Request Examples

### cURL Example
```bash
curl -X POST "https://your-domain.com/wp-json/monigpdr/v1/delete" \
  -H "Authorization: Bearer your_access_token_here" \
  -H "Content-Type: application/json"
```

### PHP Example
```php
$access_token = 'your_access_token_here';
$ch = curl_init();
curl_setopt_array($ch, array(
    CURLOPT_URL => 'https://your-domain.com/wp-json/monigpdr/v1/delete',
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => array(
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    )
));

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
```

### JavaScript Example
```javascript
const accessToken = 'your_access_token_here';
const response = await fetch('https://your-domain.com/wp-json/monigpdr/v1/delete', {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer ' + accessToken,
        'Content-Type': 'application/json'
    }
});

const result = await response.json();
```

## Response Format

### Success Response (200 OK)
```json
{
    "status": "deleted"
}
```

### Error Responses

#### Invalid Token (401 Unauthorized)
```json
{
    "error": "invalid_token"
}
```

#### User Not Found (404 Not Found)
```json
{
    "error": "user_not_found"
}
```

#### Keycloak Error (500 Internal Server Error)
```json
{
    "error": "keycloak_error"
}
```

## How It Works

### 1. Token Validation
- The API extracts the access token from the `Authorization: Bearer` header
- Validates the token using Keycloak's `/auth/realms/{realm}/protocol/openid-connect/userinfo` endpoint
- Returns 401 if the token is invalid or expired

### 2. User Identification
- Extracts user information from the token response:
  - `sub` (Keycloak user ID) - preferred
  - `email` (user email) - fallback
- Returns 400 if neither identifier is available

### 3. User Deletion
- Uses Keycloak admin credentials to delete the user
- If user ID is available, deletes directly via `/admin/realms/{realm}/users/{user_id}`
- If only email is available, searches for the user first, then deletes
- Returns 404 if user is not found in Keycloak

### 4. Logging
- Logs all operations with source `access_token`
- Includes user email, IP address, and request details
- Maintains audit trail for compliance

## Setup Instructions

### 1. Keycloak Configuration
Ensure your Keycloak server is properly configured:
- **Base URL**: Your Keycloak server URL
- **Realm**: Your Keycloak realm name
- **Client ID**: Your Keycloak client ID
- **Admin Credentials**: Valid admin username and password

### 2. Plugin Configuration
1. Go to **WordPress Admin → Monikit GDPR → Settings**
2. Configure all Keycloak settings
3. Test the Keycloak connection
4. Save settings

### 3. Mobile App Integration
- Implement OAuth2 flow to obtain access tokens from Keycloak
- Use the access token in API requests
- Handle token refresh when needed

## Security Best Practices

### 1. Token Management
- ✅ Use short-lived access tokens (15-60 minutes)
- ✅ Implement token refresh logic
- ✅ Store tokens securely in mobile apps
- ✅ Never expose tokens in client-side code

### 2. Network Security
- ✅ Use HTTPS in production
- ✅ Validate SSL certificates
- ✅ Implement certificate pinning in mobile apps
- ✅ Monitor for suspicious activity

### 3. Access Control
- ✅ Only users with valid tokens can delete their accounts
- ✅ Tokens must be issued by your Keycloak server
- ✅ Implement proper token validation
- ✅ Log all deletion attempts

### 4. Error Handling
- ✅ Don't expose internal error details
- ✅ Log errors securely
- ✅ Implement proper retry logic
- ✅ Handle network timeouts gracefully

## Logging

All API requests are logged with the following characteristics:

### Log Entries
- **Action**: `Deletion Request` (unified with UI)
- **Source**: `access_token` (distinguishes from UI requests)
- **Status Flow**: `pending` → `success` → `completed`

### Log Data
- User email (if available)
- Keycloak user ID (if available)
- IP address
- User agent
- Request timestamp
- Response status

### Viewing Logs
1. Go to **WordPress Admin → Monikit GDPR → Logs**
2. Filter by **Source: access_token** to view API-only requests
3. Export logs to CSV for analysis

## Integration Examples

### Mobile App Integration
```swift
// iOS Swift example
func deleteUserAccount(accessToken: String) async throws {
    let url = URL(string: "https://your-domain.com/wp-json/monigpdr/v1/delete")!
    var request = URLRequest(url: url)
    request.httpMethod = "POST"
    request.setValue("Bearer \(accessToken)", forHTTPHeaderField: "Authorization")
    request.setValue("application/json", forHTTPHeaderField: "Content-Type")
    
    let (data, response) = try await URLSession.shared.data(for: request)
    
    if let httpResponse = response as? HTTPURLResponse {
        switch httpResponse.statusCode {
        case 200:
            print("User deleted successfully")
        case 401:
            throw APIError.invalidToken
        case 404:
            throw APIError.userNotFound
        default:
            throw APIError.serverError
        }
    }
}
```

### Android Integration
```kotlin
// Android Kotlin example
suspend fun deleteUserAccount(accessToken: String): Result<Unit> {
    return try {
        val url = "https://your-domain.com/wp-json/monigpdr/v1/delete"
        val response = client.post(url) {
            header("Authorization", "Bearer $accessToken")
            header("Content-Type", "application/json")
        }
        
        when (response.status.value) {
            200 -> Result.success(Unit)
            401 -> Result.failure(APIException("Invalid token"))
            404 -> Result.failure(APIException("User not found"))
            else -> Result.failure(APIException("Server error"))
        }
    } catch (e: Exception) {
        Result.failure(e)
    }
}
```

### Web Application Integration
```javascript
// JavaScript/TypeScript example
async function deleteUserAccount(accessToken: string): Promise<void> {
    const response = await fetch('https://your-domain.com/wp-json/monigpdr/v1/delete', {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${accessToken}`,
            'Content-Type': 'application/json'
        }
    });
    
    if (!response.ok) {
        const error = await response.json();
        switch (response.status) {
            case 401:
                throw new Error('Invalid token');
            case 404:
                throw new Error('User not found');
            default:
                throw new Error('Server error');
        }
    }
    
    console.log('User deleted successfully');
}
```

## Troubleshooting

### Common Issues

#### 401 Unauthorized
- Check if the access token is valid and not expired
- Verify the token was issued by your Keycloak server
- Ensure the token contains user information (sub or email)

#### 404 Not Found
- Verify the user exists in Keycloak
- Check if the user was already deleted
- Ensure Keycloak admin credentials are correct

#### 500 Internal Server Error
- Check Keycloak connectivity
- Verify Keycloak admin credentials
- Review server error logs

### Debug Steps
1. **Check Token**: Verify the access token is valid using Keycloak's userinfo endpoint
2. **Test Keycloak**: Use the "Test Keycloak Connection" button in plugin settings
3. **Review Logs**: Check the logs section for detailed error messages
4. **Check Network**: Ensure your application can reach the WordPress site

## Compliance

### GDPR Compliance
- ✅ User-initiated deletion (requires valid access token)
- ✅ Complete audit trail
- ✅ Secure authentication
- ✅ No data retention beyond deletion

### Apple App Store Compliance
- ✅ OAuth2-based authentication
- ✅ User-initiated account deletion
- ✅ No additional authentication required
- ✅ Immediate account deletion

## Support

For technical support or questions about the API:

1. Check the plugin logs for detailed error information
2. Verify Keycloak configuration is correct
3. Test with valid access tokens
4. Contact the plugin developer with specific error details

## Version History

- **v1.2.0**: OAuth2-based API with access token authentication
- **v1.1.0**: Internal API key-based authentication (deprecated)
- **v1.0.0**: Initial release with UI-only deletion 