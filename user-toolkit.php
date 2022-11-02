<?php
/**
 *
 * @link              https://deryckoe.com/user-toolkit
 * @since             1.0.0
 * @package           User_Toolkit
 *
 * @wordpress-plugin
 * Plugin Name:       User Toolkit
 * Plugin URI:        https://deryckoe.com/user-toolkit
 * Description:       The missing user tools and activity data that you need and don't have by default.
 * Version:           1.0.0
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
define( 'UT_VERSION', '1.0.0' );

/**
 * Plugin paths
 */
define( 'UT_DIR', plugin_dir_path( __FILE__ ) );
define( 'UT_URL', plugins_url( '/', __FILE__ ) );
define( 'UT_LANGUAGES_DIR', basename( dirname( __FILE__ ) ) );
define( 'UT_DATE_FORMAT', 'd/m/Y' );
define( 'UT_TIME_FORMAT', 'g:i a' );

load_plugin_textdomain( 'user-toolkit', false, UT_LANGUAGES_DIR . '/languages' );

// Composer autoload
require __DIR__ . '/vendor/autoload.php';

use UserToolkit\UserTools;

function UserTools(): UserTools {
	return UserTools::instance();
}

UserTools()->init();