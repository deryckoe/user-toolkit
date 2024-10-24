<?php
/**
 *
 * @link              https://deryckoe.com/user-toolkit
 * @package           User_Toolkit
 *
 * @wordpress-plugin
 * Plugin Name:       User Toolkit
 * Plugin URI:        https://deryckoe.com/user-toolkit
 * Description:       The missing user tools and activity data that you need and don't have by default.
 * Version:           1.2.4
 * Author:            Deryck Oñate
 * Author URI:        http://deryckoe.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       user-toolkit
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
define( 'USRTK_VERSION', '1.2.4' );

/**
 * Plugin paths
 */
define( 'USRTK_DIR', plugin_dir_path( __FILE__ ) );
define( 'USRTK_URL', plugins_url( '/', __FILE__ ) );
define( 'USRTK_LANGUAGES_DIR', basename( dirname( __FILE__ ) ) );
define( 'USRTK_DATE_FORMAT', 'd/m/Y' );
define( 'USRTK_TIME_FORMAT', 'g:i a' );
define( 'USRTK_COOKIE_USER_SWITCH', 'wp_usrtk_user_switched_' . COOKIEHASH );
define( 'USRTK_COOKIE_USER_FROM', 'wp_usrtk_user_from_' . COOKIEHASH );

load_plugin_textdomain( 'user-toolkit', false, USRTK_LANGUAGES_DIR . '/languages' );

// Composer autoload
require __DIR__ . '/vendor/autoload.php';

use UserToolkit\UserTools;

function USRTK_UserTools(): UserTools {
	return UserTools::instance();
}

USRTK_UserTools()->init();