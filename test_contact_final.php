<?php
/**
 * Final Contact User Deletion Test
 * 
 * This script tests the Contact deletion API endpoint
 * You need to manually update the settings below from your WordPress admin
 */

echo "🔐 Final Contact User Deletion Test\n";
echo "==================================\n\n";

// ⚠️ UPDATE THESE WITH YOUR ACTUAL WORDPRESS ADMIN SETTINGS ⚠️
echo "⚠️  PLEASE UPDATE THE SETTINGS BELOW WITH VALUES FROM YOUR WORDPRESS ADMIN:\n";
echo "   Go to WordPress Admin → Monikit Settings and copy the values\n\n";

// Test credentials - UPDATE THESE
$username = 'your_username';
$email = 'your_email@example.com';
$password = 'your_password';

// Plugin settings - UPDATE THESE FROM YOUR WORDPRESS ADMIN
$keycloak_base_url = 'https://your-keycloak-server.com/';
$contact_realm = 'Contact';
$client_id = 'admin-cli';
$contact_client_secret = 'your_contact_client_secret_here';

echo "Current Test Settings:\n";
echo "- Keycloak Base URL: $keycloak_base_url\n";
echo "- Contact Realm: $contact_realm\n";  
echo "- Client ID: $client_id\n";
echo "- Contact Client Secret: " . (($contact_client_secret === 'UPDATE_ME') ? '❌ NOT SET' : '✅ SET (' . substr($contact_client_secret, 0, 8) . '...)') . "\n\n";

echo "Test User:\n";
echo "- Username: $username\n";
echo "- Email: $email\n";
echo "- Password: $password\n\n";

if ($contact_client_secret === 'your_contact_client_secret_here') {
    echo "❌ ERROR: Please update the \$contact_client_secret variable above.\n";
    echo "   1. Go to WordPress Admin → Monikit Settings\n";
    echo "   2. Copy the 'Contact Client Secret' value\n";
    echo "   3. Update line ~19 in this script\n";
    echo "   4. Run the test again\n\n";
    exit(1);
}

// Step 1: Get Contact User Token
echo "🔄 Step 1: Getting Contact User Token...\n";

$base_url = rtrim($keycloak_base_url, '/');
if (strpos($base_url, '/auth') === false) {
    $base_url .= '/auth';
}

$user_token_url = $base_url . '/realms/' . $contact_realm . '/protocol/openid-connect/token';
echo "   Token URL: $user_token_url\n";

$user_post_data = array(
    'grant_type' => 'password',
    'client_id' => $client_id,
    'client_secret' => $contact_client_secret,
    'username' => $username,
    'password' => $password
);

$ch = curl_init();
curl_setopt_array($ch, array(
    CURLOPT_URL => $user_token_url,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($user_post_data),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/x-www-form-urlencoded'
    ),
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false
));

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    echo "❌ cURL Error: $curl_error\n";
    exit(1);
}

echo "   Response Code: $http_code\n";

if ($http_code !== 200) {
    echo "   Response: $response\n\n";
    echo "❌ Failed to get Contact user token.\n";
    echo "   This could mean:\n";
    echo "   - Contact realm name is incorrect\n";
    echo "   - Contact client secret is wrong  \n";
    echo "   - User credentials are incorrect\n";
    echo "   - User doesn't exist in Contact realm\n\n";
    
    echo "🔧 Troubleshooting:\n";
    echo "   1. Check WordPress Admin → Monikit Settings:\n";
    echo "      - Verify 'Contact Realm' field matches Keycloak\n";
    echo "      - Verify 'Contact Client Secret' is correct\n";
    echo "   2. Check Keycloak Contact realm:\n";
    echo "      - User '$username' exists\n";
    echo "      - Client '$client_id' has correct secret\n";
    echo "      - Client allows 'password' grant type\n\n";
    exit(1);
}

$token_data = json_decode($response, true);
if (!$token_data || !isset($token_data['access_token'])) {
    echo "❌ Invalid token response format\n";
    echo "   Response: $response\n";
    exit(1);
}

$user_access_token = $token_data['access_token'];
echo "✅ Contact user token obtained successfully\n\n";

// Step 2: Test Contact API Endpoint
echo "🔄 Step 2: Testing Contact API Endpoint...\n";

$api_url = 'http://localhost:10008/wp-json/monigpdr/v1/delete/contact';
echo "   API URL: $api_url\n";

$ch = curl_init();
curl_setopt_array($ch, array(
    CURLOPT_URL => $api_url,
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => array(
        'Authorization: Bearer ' . $user_access_token,
        'Content-Type: application/json'
    ),
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false
));

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    echo "❌ cURL Error: $curl_error\n";
    exit(1);
}

echo "   Response Code: $http_code\n";
echo "   Response: $response\n\n";

// Analyze results
if ($http_code === 200) {
    echo "🎉 SUCCESS! Contact user deletion completed!\n\n";
    
    $response_data = json_decode($response, true);
    if (is_array($response_data)) {
        if (isset($response_data['status'])) {
            echo "✅ Status: " . $response_data['status'] . "\n";
        }
        if (isset($response_data['message'])) {
            echo "📝 Message: " . $response_data['message'] . "\n";
        }
    }
    
    echo "\n🎯 Test Result: PASSED\n";
    echo "   - Contact user token: ✅ SUCCESS\n";
    echo "   - Contact API deletion: ✅ SUCCESS\n";
    echo "   - User '$username' deleted from Contact realm\n";
    
} else {
    echo "❌ Contact user deletion failed\n";
    
    $response_data = json_decode($response, true);
    if (is_array($response_data) && isset($response_data['error'])) {
        echo "   Error Code: " . $response_data['error'] . "\n";
        
        switch ($response_data['error']) {
            case 'keycloak_error':
                echo "   Issue: Keycloak admin configuration problem\n";
                echo "   Fix: Check WordPress admin credentials and permissions\n";
                break;
            case 'invalid_token':
                echo "   Issue: Token validation failed\n";  
                echo "   Fix: Token expired or invalid for Contact realm\n";
                break;
            case 'user_not_found':
                echo "   Issue: User not found in Contact realm\n";
                echo "   Fix: User may already be deleted or doesn't exist\n";
                break;
            case 'rate_limit_exceeded':
                echo "   Issue: Rate limit exceeded\n";
                echo "   Fix: Try again in a few minutes\n";
                break;
            default:
                echo "   Issue: Unknown error - " . $response_data['error'] . "\n";
        }
    }
    
    echo "\n🎯 Test Result: FAILED\n";
    echo "   - Contact user token: ✅ SUCCESS\n";
    echo "   - Contact API deletion: ❌ FAILED\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "📋 SUMMARY\n";
echo "   Plugin: Contact User Deletion\n";
echo "   Endpoint: /wp-json/monigpdr/v1/delete/contact\n";
echo "   User: $username\n";  
echo "   Realm: $contact_realm\n";
echo "   Result: " . (($http_code === 200) ? "✅ SUCCESS" : "❌ FAILED") . "\n";
echo str_repeat("=", 50) . "\n";
?>