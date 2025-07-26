<?php
/**
 * Test Admin Page
 * 
 * This file is used to test whether the admin page is working properly.
 * The Monikit settings page should appear under the Monikit menu in the WordPress admin panel.
 */

// To test in WordPress admin panel:
// 1. Upload this file to the plugin folder
// 2. Go to "Monikit" menu in WordPress admin panel
// 3. Check if the settings page loads properly

// Test functions
function test_monikit_admin_page() {
    // Check if admin class is loaded
    if ( class_exists( 'Monikit_App_Gdpr_User_Data_Deletion_Admin' ) ) {
        echo "✅ Admin class loaded successfully.\n";
    } else {
        echo "❌ Admin class failed to load.\n";
    }
    
    // Check if main plugin class is loaded
    if ( class_exists( 'Monikit_App_Gdpr_User_Data_Deletion' ) ) {
        echo "✅ Main plugin class loaded successfully.\n";
    } else {
        echo "❌ Main plugin class failed to load.\n";
    }
    
    // Check if MONIGPDR function works
    if ( function_exists( 'MONIGPDR' ) ) {
        $plugin = MONIGPDR();
        echo "✅ MONIGPDR function is working.\n";
        
        // Check if admin object exists
        if ( isset( $plugin->admin ) ) {
            echo "✅ Admin object exists.\n";
        } else {
            echo "❌ Admin object does not exist.\n";
        }
    } else {
        echo "❌ MONIGPDR function not found.\n";
    }
    
    // Check if settings exist
    $settings = get_option( 'monikit_settings', array() );
    if ( ! empty( $settings ) ) {
        echo "✅ Settings exist: " . count( $settings ) . " settings found.\n";
    } else {
        echo "⚠️ Settings not yet created (normal on first load).\n";
    }
}

// If this file is run directly, execute the test function
if ( defined( 'ABSPATH' ) ) {
    add_action( 'admin_init', 'test_monikit_admin_page' );
} 