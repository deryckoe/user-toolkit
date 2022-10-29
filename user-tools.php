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

// TODO: Make column sortable

function ut_user_columns_headers( $columns ): array {
	return array_merge( $columns, [
		'can_login'  => __( 'Active', 'user-tools' ),
		'last_login' => __( 'Last login', 'user-tools' ),
		'registered' => __( 'Registered', 'user-tools' ),
		'id'         => __( 'ID', 'user-tools' ),
	] );
}

add_filter( 'manage_users_columns', 'ut_user_columns_headers' );

function ut_user_columns_content( $value, $column, $user_id ) {

	switch ( $column ) {
		case 'last_login':
			return ut_get_last_login( $user_id );

		case 'can_login':
			$active = ut_can_user_login( $user_id );

			return '<div class="ut-toggle" data-active="' . $active . '" data-user-id="' . $user_id . '"><div class="switch"></div></div>';

		case 'registered':
			return ut_get_registered( $user_id );
		case 'id':

			return $user_id;
	}

	return $value;

}


add_filter( 'manage_users_custom_column', 'ut_user_columns_content', 10, 3 );

function ut_get_last_login( $user_id ): ?string {
	$last_login_timestamp = get_user_meta( $user_id, 'last_login', true );
	$date                 = wp_date( UT_DATE_FORMAT, $last_login_timestamp, wp_timezone() );
	$time                 = wp_date( UT_TIME_FORMAT, $last_login_timestamp, wp_timezone() );

	$last_login = __( 'Never', 'user-tools' );

	if ( ! empty( $last_login_timestamp ) ) {
		$last_login = '<span class="time_formatted">' . sprintf( __( '%s at %s', 'user-tools' ), $date, $time ) . '</span>';

		$human      = human_time_diff( $last_login_timestamp, current_time( 'timestamp', true ) );
		$last_login .= '<br><span class="time_diff">' . sprintf( __( '%s ago', 'user-tools' ), $human ) . '</span>';
	}

	return $last_login;
}

function ut_get_registered( $user_id ): string {
	$user = get_user_by( 'id', $user_id );

	$date = wp_date( UT_DATE_FORMAT, strtotime( $user->user_registered ), wp_timezone() );
	$time = wp_date( UT_TIME_FORMAT, strtotime( $user->user_registered ), wp_timezone() );

	$registered = '<span class="time_formatted">' . sprintf( __( '%s at %s', 'user-tools' ), $date, $time ) . '</span>';
	$human      = human_time_diff( strtotime( $user->user_registered ), current_time( 'timestamp', true ) );
	$registered .= '<br><span class="time_diff">' . sprintf( __( '%s ago', 'user-tools' ), $human ) . '</span>';

	return $registered;
}

function ut_user_columns_sortable( $columns ): array {
	return array_merge( $columns, [ 'last_login' => 'last_login', 'registered' => 'registered', 'id' => 'id' ] );
}

add_filter( 'manage_users_sortable_columns', 'ut_user_columns_sortable' );

function ut_user_columns_sort( $query ): void {

	if ( ! is_admin() ) {
		return;
	}

	switch ( $query->get( 'orderby' ) ) {
		case 'last_login':
			$query->set( 'meta_key', 'last_login' );
			$query->set( 'orderby', 'meta_value_num' );
			break;
		case 'registered':
			$query->set( 'orderby', 'user_registered' );
			break;
		case 'id':
			$query->set( 'orderby', 'id' );
			break;
		default:
	}
}

add_action( 'pre_get_posts', 'ut_user_columns_sort' );

function ut_register_user_login( $user_login, $user ): void {
	update_user_meta( $user->ID, 'last_login', current_time( 'timestamp', true ) );
}

add_action( 'wp_login', 'ut_register_user_login', 10, 2 );

function ut_profile_content( $user ) {
	?>
    <h2><?php _e( 'User Tools', 'user-tools' ) ?></h2>
    <table class="form-table">
		<?php if ( current_user_can( 'edit_user' ) ) : ?>
            <tr>
                <th scope="row"><?php _e( 'Login active', 'user-tools' ) ?></th>
                <td>
                    <div class="time_wrapper">
                        <label for="can_login">
                            <input name="can_login" type="checkbox" id="can_login"
                                   value="1" <?php checked( ut_can_user_login( $user->ID ), 1 ) ?>>
							<?php _e( 'Activate user login', 'user-tools' ) ?></label>
                    </div>
                </td>
            </tr>
		<?php endif; ?>
        <tr>
            <th scope="row"><label><?php _e( 'Registered', 'user-tools' ) ?></label></th>
            <td>
                <div class="time_wrapper">
					<?php echo ut_get_registered( $user->ID ); ?>
                </div>
            </td>
        </tr>
        <tr>
            <th scope="row"><label><?php _e( 'Last login', 'user-tools' ) ?></label></th>
            <td>
                <div class="time_wrapper">
					<?php echo ut_get_last_login( $user->ID ); ?>
                </div>
            </td>
        </tr>
    </table>
	<?php
}

add_action( 'edit_user_profile', 'ut_profile_content' );
add_action( 'show_user_profile', 'ut_profile_content' );

function ut_save_profile_settings( $user_id ): bool {
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return false;
	}

	$value = $_POST['can_login'] ?? '0';

	update_user_meta( $user_id, 'can_login', $value );

	return true;
}

add_action( 'personal_options_update', 'ut_save_profile_settings' );
add_action( 'edit_user_profile_update', 'ut_save_profile_settings' );


function ut_tools_scripts() {
	?>
    <style>
</style>
	<?php
}

add_action( 'admin_print_styles', 'ut_tools_scripts' );

function ut_add_fields() {
	register_rest_field(
		'user',
		'can_login',
		[
			'get_callback'    => function ( $user ) {
				return (int) get_user_meta( $user['id'], 'can_login', true );
			},
			'update_callback' => function ( $value, $user, $field_name ) {
				return update_user_meta( $user->id, $field_name, $value );
			},
			'schema'          => [
				'type'        => 'number',
				'arg_options' => [
					'sanitize_callback' => function ( $value ) {
						return (int) sanitize_text_field( $value );
					},
					'validate_callback' => function ( $value ) {
						return in_array( $value, [ 0, 1 ] );
					},
				],
			]
		]
	);
}

add_action( 'rest_api_init', 'ut_add_fields' );

function ut_enqueue_scripts() {
	wp_enqueue_script( 'user-tools', UT_URL . 'assets/app.js', [ 'wp-api' ], UT_VERSION, true );
	wp_enqueue_style( 'user-tools', UT_URL . 'assets/app.css', [], UT_VERSION );
}

add_action( 'admin_enqueue_scripts', 'ut_enqueue_scripts' );

function ut_user_can_login( $user ) {

	if ( ! $user instanceof WP_User ) {
		return $user;
	}

	$can_login = ut_can_user_login( $user->ID );

	if ( $can_login != 1 ) {
		$user = new WP_Error( 'authentication_deactivated', __( '<strong>Error</strong>: Username is deactivated. Please contact your manager for further information.', 'user-tools' ) );
	}

	return $user;
}

add_filter( 'authenticate', 'ut_user_can_login', 30 );


function ut_can_user_login( $user_id ): int {
	$can_login = get_user_meta( $user_id, 'can_login', true );

	return ( $can_login === '' || $can_login === '1' ) ? 1 : 0;
}


function ut_register_user( $user_id ) {
	$value = get_option( 'ut_can_login', '1' );
	update_user_meta( $user_id, 'can_login', $value );
}

add_action( 'user_register', 'ut_register_user' );


function ut_render_custom_filter_options() {

	$can_login = $_GET['can_login'] ?? '';
	$all_label = isset( $_GET['can_login'] ) && $_GET['can_login'] !== '-1' ? __( 'All', 'user-tools' ) : __( 'Login status', 'user-tools' )

	?>

    <div class="alignleft actions">
        <form method="get">
            <label class="screen-reader-text" for="new_role"><?php _e( 'All login status', 'user-tools' ) ?></label>
            <select name="can_login">
                <option value="-1"><?php echo $all_label ?></option>
                <option value="1" <?php selected( $can_login, 1 ) ?>><?php _e( 'Enabled (Active)', 'user-tools' ) ?></option>
                <option value="0"<?php selected( $can_login, 0 ) ?>><?php _e( 'Disabled', 'user-tools' ) ?></option>
            </select>
            <input type="submit" class="button action" value="Filter">
        </form>
    </div>
	<?php
}

add_action( 'manage_users_extra_tablenav', 'ut_render_custom_filter_options' );


function ut_user_filter( $query ): void {

	if ( ! is_admin() ) {
		return;
	}

	global $pagenow;

	if ( 'users.php' !== $pagenow ) {
		return;
	}

	if ( isset( $_GET['can_login'] ) && ! in_array( $_GET['can_login'], [ '0', '1' ] ) ) {
		return;
	}

	if ( ! isset( $_GET['can_login'] ) ) {
		return;
	}

	$meta_query = [
		[
			'key'     => 'can_login',
			'value'   => $_GET['can_login'],
			'compare' => '='
		]
	];

	$query->set( 'meta_query', $meta_query );
}

add_action( 'pre_get_users', 'ut_user_filter' );
