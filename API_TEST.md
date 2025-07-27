# Monikit GDPR API Testing Guide

## API Endpoint
`POST /wp-json/monigpdr/v1/delete`

## Prerequisites
1. Enable the API in plugin settings (🔐 Developer API section)
2. Generate an API key
3. Ensure Keycloak settings are configured

## Test Cases

### 1. Valid Deletion Request
```bash
curl -X POST "https://your-site.com/wp-json/monigpdr/v1/delete" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "api_key": "your-api-key-here"
  }'
```

**Expected Response (200 OK):**
```json
{
  "status": "deleted",
  "email": "user@example.com"
}
```

### 2. Invalid API Key
```bash
curl -X POST "https://your-site.com/wp-json/monigpdr/v1/delete" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "api_key": "invalid-key"
  }'
```

**Expected Response (401 Unauthorized):**
```json
{
  "error": "invalid_api_key"
}
```

### 3. User Not Found
```bash
curl -X POST "https://your-site.com/wp-json/monigpdr/v1/delete" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "nonexistent@example.com",
    "api_key": "your-api-key-here"
  }'
```

**Expected Response (404 Not Found):**
```json
{
  "error": "user_not_found"
}
```

### 4. API Disabled
```bash
curl -X POST "https://your-site.com/wp-json/monigpdr/v1/delete" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "api_key": "your-api-key-here"
  }'
```

**Expected Response (503 Service Unavailable):**
```json
{
  "error": "api_disabled"
}
```

### 5. Missing Parameters
```bash
curl -X POST "https://your-site.com/wp-json/monigpdr/v1/delete" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com"
  }'
```

**Expected Response (400 Bad Request):**
```json
{
  "code": "rest_missing_callback_param",
  "message": "Missing parameter(s): api_key",
  "data": {
    "status": 400,
    "params": ["api_key"]
  }
}
```

## Security Notes
- API key is stored securely in plugin options
- All requests are logged with source "API"
- Failed authentication attempts are logged
- API can be completely disabled via settings
- No Keycloak credentials are exposed in responses

## Logging
All API requests are logged in the plugin's logs section with:
- Source: "API"
- IP address tracking
- User agent tracking
- Success/failure status
- Detailed error messages (for debugging) 