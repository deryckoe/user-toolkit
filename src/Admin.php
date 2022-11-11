<?php

namespace UserToolkit;

use function USRTK_UserTools;

class Admin {

	public function init() {
		$this->actions();
	}

	public function actions() {
		add_filter( 'manage_users_columns', [ $this, 'columnHeaders' ] );
		add_filter( 'manage_users_custom_column', [ $this, 'columnContent' ], 10, 3 );
		add_filter( 'manage_users_sortable_columns', [ $this, 'columnSortable' ] );
		add_filter( 'user_row_actions', [ $this, 'userRowAction' ], 10, 2 );
		add_action( 'pre_get_posts', [ $this, 'sortColumns' ] );
		add_action( 'manage_users_extra_tablenav', [ $this, 'columnFilters' ] );
		add_action( 'pre_get_users', [ $this, 'filterColumns' ] );
		add_action( 'edit_user_profile', [ $this, 'userProfileFields' ] );
		add_action( 'show_user_profile', [ $this, 'userProfileFields' ] );
		add_action( 'personal_options_update', [ $this, 'saveUserFields' ] );
		add_action( 'edit_user_profile_update', [ $this, 'saveUserFields' ] );
		add_action( 'login_init', [ $this, 'switchUser' ] );
		add_filter( 'admin_footer_text', [ $this, 'backLink' ] );

	}

	public function switchUser() {

		$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : false;

		if ( ! $action && in_array( $action, [ 'switch_user', 'restore_user' ] ) ) {
			return;
		}

		if ( ! isset( $_GET['_wpnonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'switch_user' ) ) {
			return;
		}

		$user_id = intval( $_GET['user_id'] );

		if ( ! $user_id ) {
			return;
		}

		$user = get_user_by( 'id', $_GET['user_id'] );

		if ( false === $user ) {
			return;
		}

		// TODO: https://developer.wordpress.org/reference/functions/wp_admin_bar_my_account_item/

		$user_from_id = intval( $_GET['user_from'] );

		if ( ! $user_from_id ) {
			return;
		}


		if ( $_GET['action'] === 'restore_user' ) {
			if ( (int) $_COOKIE['user_switched'] !== $user_from_id ) {
				wp_die( __( 'You are not allowed to perform this action.', 'user-toolkit' ) );
			}
		}

		wp_clear_auth_cookie();
		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID );

		$user_from = get_user_by( 'id', $user_from_id );

		if ( false !== $user_from && $_GET['action'] === 'switch_user' ) {
			setcookie( 'user_from', $user_from->ID, time() + DAY_IN_SECONDS, '/', COOKIE_DOMAIN, is_ssl(), true );
			setcookie( 'user_switched', $user->ID, time() + DAY_IN_SECONDS, '/', COOKIE_DOMAIN, is_ssl(), true );
		} else {
			setcookie( 'user_from', $user_from->ID, time() - 3600, '/', COOKIE_DOMAIN, is_ssl(), true );
			setcookie( 'user_switched', $user->ID, time() - 3600, '/', COOKIE_DOMAIN, is_ssl(), true );
		}

		$redirect_to = user_admin_url();
		wp_safe_redirect( $redirect_to );
		exit;
	}

	public function columnHeaders( $columns ): array {
		return array_merge( $columns, [
			'can_login'  => __( 'Active', 'user-toolkit' ),
			'last_login' => __( 'Last login', 'user-toolkit' ),
			'registered' => __( 'Registered', 'user-toolkit' ),
			'id'         => __( 'ID', 'user-toolkit' ),
		] );
	}

	public function columnContent( $value, $column, $user_id ) {

		switch ( $column ) {
			case 'last_login':
				return USRTK_UserTools()->user( $user_id )->lastLogin();

			case 'can_login':
				$active = USRTK_UserTools()->user( $user_id )->canLogin();

				if ( $user_id === get_current_user_id() ) {
					$active_label = ( $active === 1 ) ? __( 'On', 'user-toolkit' ) : __( 'Off', 'user-toolkit' );

					return '<div class="ut-readonly-toggle" data-active="' . $active . '">' . $active_label . '</div>';
				}

				return '<div class="ut-toggle" data-active="' . $active . '" data-user-id="' . $user_id . '">
						    <div class="switch"></div>
					    </div>';

			case 'registered':
				return USRTK_UserTools()->user( $user_id )->registered();
			case 'id':

				return $user_id;
		}

		return $value;

	}

	public function columnSortable( $columns ): array {
		return array_merge( $columns, [ 'last_login' => 'last_login', 'registered' => 'registered', 'id' => 'id' ] );
	}

	public function userRowAction( $actions, $user ) {

		if ( ! current_user_can( 'remove_users' ) && ! current_user_can( 'manage_network_users' ) ) {
			return $actions;
		}

		if ( $user->ID === get_current_user_id() ) {
			return $actions;
		}

		$login_url = add_query_arg( [
			'action'    => 'switch_user',
			'user_id'   => $user->ID,
			'user_from' => get_current_user_id(),
		], wp_login_url() );

		$safe_login_url = wp_nonce_url( $login_url, 'switch_user' );

		$switch = '<a href="' . $safe_login_url . '">' . __( 'Switch to', 'user-toolkit' ) . '</a>';

		return array_merge( $actions, [ $switch ] );
	}

	public function sortColumns( $query ): void {

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


	public function columnFilters() {

		$can_login = isset( $_GET['can_login'] ) ? sanitize_text_field( $_GET['can_login'] ) : '';

		$all_label = isset( $can_login ) && $can_login !== '-1' ? __( 'All', 'user-toolkit' ) : __( 'Login status', 'user-toolkit' )

		?>

        <div class="alignleft actions">
            <form method="get">
                <label class="screen-reader-text"
                       for="can_login"><?php _e( 'All login status', 'user-toolkit' ) ?></label>
                <select name="can_login" id="can_login">
                    <option value="-1"><?php echo $all_label ?></option>
                    <option value="1" <?php selected( $can_login, 1 ) ?>><?php _e( 'Enabled (Active)', 'user-toolkit' ) ?></option>
                    <option value="0"<?php selected( $can_login, 0 ) ?>><?php _e( 'Disabled', 'user-toolkit' ) ?></option>
                </select>
                <input type="submit" class="button action" value="<?php _e( 'Filter', 'user-toolkit' ) ?>">
            </form>
        </div>
		<?php
	}

	public function filterColumns( $query ): void {

		if ( ! is_admin() ) {
			return;
		}

		global $pagenow;

		if ( 'users.php' !== $pagenow ) {
			return;
		}

		$can_login = isset( $_GET['can_login'] ) ? sanitize_text_field( $_GET['can_login'] ) : '';

		if ( ! in_array( $can_login, [ '0', '1' ] ) ) {
			return;
		}

		$meta_query = [
			[
				'key'     => 'can_login',
				'value'   => $can_login,
				'compare' => '='
			]
		];

		$query->set( 'meta_query', $meta_query );
	}

	public function userProfileFields( $user ) {
		?>
        <h2><?php _e( 'User Tools', 'user-toolkit' ) ?></h2>
        <table class="form-table">
			<?php if ( current_user_can( 'edit_user' ) && $user->ID !== get_current_user_id() ) : ?>
                <tr>
                    <th scope="row"><?php _e( 'Login active', 'user-toolkit' ) ?></th>
                    <td>
                        <div class="time_wrapper">
                            <label for="can_login">
                                <input name="can_login" type="checkbox" id="can_login"
                                       value="1" <?php checked( USRTK_UserTools()->user( $user->ID )->canLogin(), 1 ) ?>>
								<?php _e( 'Activate user login', 'user-toolkit' ) ?></label>
                        </div>
                    </td>
                </tr>
			<?php endif; ?>
            <tr>
                <th scope="row"><label><?php _e( 'Registered', 'user-toolkit' ) ?></label></th>
                <td>
                    <div class="time_wrapper">
						<?php echo USRTK_UserTools()->user( $user->ID )->registered(); ?>
                    </div>
                </td>
            </tr>
            <tr>
                <th scope="row"><label><?php _e( 'Last login', 'user-toolkit' ) ?></label></th>
                <td>
                    <div class="time_wrapper">
						<?php echo USRTK_UserTools()->user( $user->ID )->lastLogin(); ?>
                    </div>
                </td>
            </tr>
        </table>
		<?php
	}

	public function saveUserFields( $user_id ): bool {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}

		$can_login = isset( $_POST['can_login'] ) ? sanitize_text_field( $_POST['can_login'] ) : '0';

		if ( ! in_array( $can_login, [ '0', '1' ] ) ) {
			return false;
		}

		update_user_meta( $user_id, 'can_login', $can_login );

		return true;
	}

	public function backLink( $text ) {

		if ( ! isset( $_COOKIE['user_from'] ) ) {
			return $text;
		}

		$user_from_id = $_COOKIE['user_from'];
		$user_from    = get_user_by( 'id', $user_from_id );

		if ( false === $user_from ) {
			return $text;
		}

        if ( $user_from->ID === get_current_user_id() ) {
            return $text;
        }

		$login_url = add_query_arg( [
			'action'    => 'restore_user',
			'user_id'   => $user_from_id,
			'user_from' => get_current_user_id(),
		], wp_login_url() );

		$safe_login_url = wp_nonce_url( $login_url, 'switch_user' );

		return '<a href="' . $safe_login_url . '">' . sprintf( __( 'Return to %s', 'user-toolkit' ), $user_from->display_name ) . '</a>';

	}

}