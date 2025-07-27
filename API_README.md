# Monikit GDPR API Documentation

## Overview

The Monikit GDPR plugin provides a secure REST API endpoint for programmatic user deletion by email. This API is designed for internal use and integrates seamlessly with the existing Keycloak user management system.

## Features

- **Secure Authentication**: Internal API key authentication
- **Unified Logging**: Consistent logging with UI-initiated deletions
- **Keycloak Integration**: Direct integration with existing Keycloak deletion methods
- **Error Handling**: Comprehensive error responses and logging
- **Rate Limiting**: Built-in WordPress REST API rate limiting
- **IP Logging**: Automatic client IP address logging for security

## API Endpoint

```
POST /wp-json/monigpdr/v1/delete
```

### Base URL
```
https://your-domain.com/wp-json/monigpdr/v1/delete
```

## Authentication

The API uses an internal API key stored securely in the plugin settings. This key is separate from Keycloak credentials and provides an additional security layer.

### Required Headers
```
Content-Type: application/x-www-form-urlencoded
```

### Required Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `email` | string | Yes | Email address of the user to delete |
| `api_key` | string | Yes | Internal API key from plugin settings |

## Request Examples

### cURL Example
```bash
curl -X POST "https://your-domain.com/wp-json/monigpdr/v1/delete" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "email=user@example.com&api_key=your_internal_api_key"
```

### PHP Example
```php
$data = array(
    'email' => 'user@example.com',
    'api_key' => 'your_internal_api_key'
);

$ch = curl_init();
curl_setopt_array($ch, array(
    CURLOPT_URL => 'https://your-domain.com/wp-json/monigpdr/v1/delete',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($data),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => array('Content-Type: application/x-www-form-urlencoded')
));

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
```

### JavaScript Example
```javascript
const response = await fetch('https://your-domain.com/wp-json/monigpdr/v1/delete', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: new URLSearchParams({
        email: 'user@example.com',
        api_key: 'your_internal_api_key'
    })
});

const result = await response.json();
```

## Response Format

### Success Response (200 OK)
```json
{
    "status": "deleted",
    "email": "user@example.com"
}
```

### Error Responses

#### Invalid API Key (401 Unauthorized)
```json
{
    "error": "invalid_api_key"
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

#### API Disabled (503 Service Unavailable)
```json
{
    "error": "api_disabled"
}
```

## Setup Instructions

### 1. Enable the API
1. Go to **WordPress Admin → Monikit GDPR → Settings**
2. Navigate to the **🔐 Developer API** section
3. Check **"Enable Direct API Access"**
4. Click **"Save Settings"**

### 2. Generate API Key
1. In the **Internal API Key** field, click **"Generate New Key"**
2. Copy the generated key (64 characters)
3. Click **"Save Settings"** to store the key

### 3. Test the API
Use the provided examples above to test the endpoint with your API key.

## Logging

All API requests are logged with the following characteristics:

### Log Entries
- **Action**: `Deletion Request` (unified with UI)
- **Source**: `API` (distinguishes from UI requests)
- **Status Flow**: `pending` → `success` → `completed`

### Log Data
- Email address
- IP address
- User agent
- Request timestamp
- Response status
- Keycloak user ID (if available)

### Viewing Logs
1. Go to **WordPress Admin → Monikit GDPR → Logs**
2. Filter by **Source: API** to view API-only requests
3. Export logs to CSV for analysis

## Security Best Practices

### 1. API Key Management
- ✅ Generate a strong, unique API key
- ✅ Store the key securely (not in code)
- ✅ Rotate the key periodically
- ✅ Never expose the key in client-side code

### 2. Network Security
- ✅ Use HTTPS in production
- ✅ Implement IP whitelisting if possible
- ✅ Monitor API usage patterns
- ✅ Set up alerts for failed authentication attempts

### 3. Access Control
- ✅ Limit API access to trusted systems
- ✅ Implement rate limiting on your application side
- ✅ Log all API usage for audit purposes
- ✅ Regularly review access logs

### 4. Error Handling
- ✅ Don't expose internal error details
- ✅ Log errors securely
- ✅ Implement proper retry logic
- ✅ Handle network timeouts gracefully

## Production Deployment Checklist

### Before Deployment
- [ ] Generate a new API key for production
- [ ] Test the API with production Keycloak settings
- [ ] Verify HTTPS is enabled
- [ ] Check firewall/security group settings
- [ ] Set up monitoring and alerting

### After Deployment
- [ ] Test API connectivity from your application
- [ ] Verify logging is working correctly
- [ ] Monitor error rates and response times
- [ ] Set up automated health checks
- [ ] Document the API key and endpoint for your team

## Troubleshooting

### Common Issues

#### 401 Unauthorized
- Check if the API key is correct
- Verify the API is enabled in settings
- Ensure the key hasn't been regenerated

#### 404 Not Found
- Verify the user exists in Keycloak
- Check the email format
- Ensure Keycloak is accessible

#### 500 Internal Server Error
- Check Keycloak connectivity
- Verify Keycloak credentials
- Review server error logs

#### 503 Service Unavailable
- Enable the API in plugin settings
- Check if the plugin is active
- Verify WordPress REST API is working

### Debug Mode
For troubleshooting, enable WordPress debug mode and check the error logs for detailed information.

## Integration Examples

### WordPress Integration
```php
// In your WordPress theme or plugin
function delete_user_via_api($email) {
    $api_key = get_option('monikit_settings')['internal_api_key'];
    $response = wp_remote_post(home_url('/wp-json/monigpdr/v1/delete'), array(
        'body' => array(
            'email' => $email,
            'api_key' => $api_key
        )
    ));
    
    return wp_remote_retrieve_body($response);
}
```

### External Application Integration
```php
// In your external application
class MonikitGDPRClient {
    private $api_url;
    private $api_key;
    
    public function __construct($api_url, $api_key) {
        $this->api_url = $api_url;
        $this->api_key = $api_key;
    }
    
    public function deleteUser($email) {
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $this->api_url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(array(
                'email' => $email,
                'api_key' => $this->api_key
            )),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array('Content-Type: application/x-www-form-urlencoded')
        ));
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return array(
            'status_code' => $http_code,
            'response' => json_decode($response, true)
        );
    }
}
```

## Support

For technical support or questions about the API:

1. Check the plugin logs for detailed error information
2. Review this documentation
3. Contact the plugin developer with specific error details

## Version History

- **v1.0.0**: Initial API release with unified logging system 