<?php
/**
 * Monikit App GDPR User Data Deletion
 *
 * @package       MONIGPDR
 * @author        Hasan Burak TASYUREK
 * @license       gplv2
 * @version       1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:   Monikit App GDPR User Data Deletion
 * Plugin URI:    https://github.com/hburaktasyurek/monikit-gdpr-user-deletion
 * Description:   Allows Monikit users to request deletion or anonymization of their account in accordance with GDPR using secure Keycloak API integration.
 * Version:       1.0.0
 * Author:        Hasan Burak TASYUREK
 * Author URI:    https://hbglobal.dev
 * Text Domain:   monikit-app-gdpr-user-data-deletion
 * Domain Path:   /languages
 * License:       GPLv2
 * License URI:   https://www.gnu.org/licenses/gpl-2.0.html
 *
 * You should have received a copy of the GNU General Public License
 * along with Monikit App GDPR User Data Deletion. If not, see <https://www.gnu.org/licenses/gpl-2.0.html/>.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * HELPER COMMENT START
 * 
 * This file contains the main information about the plugin.
 * It is used to register all components necessary to run the plugin.
 * 
 * The comment above contains all information about the plugin 
 * that are used by WordPress to differenciate the plugin and register it properly.
 * It also contains further PHPDocs parameter for a better documentation
 * 
 * The function MONIGPDR() is the main function that you will be able to 
 * use throughout your plugin to extend the logic. Further information
 * about that is available within the sub classes.
 * 
 * HELPER COMMENT END
 */

// Plugin name
define( 'MONIGPDR_NAME',			'Monikit App GDPR User Data Deletion' );

// Plugin version
define( 'MONIGPDR_VERSION',		'1.0.0' );

// Plugin Root File
define( 'MONIGPDR_PLUGIN_FILE',	__FILE__ );

// Plugin base
define( 'MONIGPDR_PLUGIN_BASE',	plugin_basename( MONIGPDR_PLUGIN_FILE ) );

// Plugin Folder Path
define( 'MONIGPDR_PLUGIN_DIR',	plugin_dir_path( MONIGPDR_PLUGIN_FILE ) );

// Plugin Folder URL
define( 'MONIGPDR_PLUGIN_URL',	plugin_dir_url( MONIGPDR_PLUGIN_FILE ) );

/**
 * Load the main class for the core functionality
 */
require_once MONIGPDR_PLUGIN_DIR . 'core/class-monikit-app-gdpr-user-data-deletion.php';

/**
 * The main function to load the only instance
 * of our master class.
 *
 * @author  Hasan Burak TASYUREK
 * @since   1.0.0
 * @return  object|Monikit_App_Gdpr_User_Data_Deletion
 */
function MONIGPDR() {
	return Monikit_App_Gdpr_User_Data_Deletion::instance();
}

MONIGPDR();
