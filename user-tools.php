<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://deryckoe.com/user-tools
 * @since             1.0.0
 * @package           User_Tools
 *
 * @wordpress-plugin
 * Plugin Name:       User Tools
 * Plugin URI:        https://deryckoe.com/user-tools
 * Description:       Simple but useful tools missing in user management
 * Version:           0.0.1-dev
 * Author:            Deryck Oñate
 * Author URI:        http://deryckoe.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       user-tools
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
define( 'UT_VERSION', '0.0.1-dev' );

/**
 * Plugin paths
 */
define( 'UT_DIR', plugin_dir_path( __FILE__ ) );
define( 'UT_URL', plugins_url( '/', __FILE__ ) );
define( 'UT_LANGUAGES_DIR', basename( dirname( __FILE__ ) ) );
define( 'UT_DATE_FORMAT', 'd/m/Y' );
define( 'UT_TIME_FORMAT', 'g:i a' );

load_plugin_textdomain( 'user-tools', false, UT_LANGUAGES_DIR . '/languages' );

// Composer autoload
require __DIR__ . '/vendor/autoload.php';

use UserTools\UserTools;

function UserTools(): UserTools {
	return UserTools::instance();
}

UserTools()->init();