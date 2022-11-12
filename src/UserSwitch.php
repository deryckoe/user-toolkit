<?php

namespace UserToolkit;

class UserSwitch {

	public function init() {
		$this->actions();
	}

	public function actions() {
		add_filter( 'user_row_actions', [ $this, 'userRowAction' ], 10, 2 );
		add_action( 'login_init', [ $this, 'switchUser' ] );
		add_action( 'admin_notices', [ $this, 'restoreUserNotice' ] );
		add_action( 'wp_footer', [ $this, 'restoreUserNotice' ] );
		add_action( 'admin_bar_menu', [ $this, 'restoreUserMenu'], 10 );
		add_action( 'usrtk_after_profile_settings', [ $this, 'userProfileFields' ] );
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
			if ( ! isset( $_COOKIE['user_switched'] ) || (int) $_COOKIE['user_switched'] !== $user_from_id ) {
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

	public function restoreUserNotice( $text ) {

		if ( ! isset( $_COOKIE['user_from'] ) ) {
			return;
		}

		$user_from_id = $_COOKIE['user_from'];
		$user_from    = get_user_by( 'id', $user_from_id );

		if ( false === $user_from ) {
			return;
		}

		if ( $user_from->ID === get_current_user_id() ) {
			return;
		}

		$login_url = add_query_arg( [
			'action'    => 'restore_user',
			'user_id'   => $user_from_id,
			'user_from' => get_current_user_id(),
		], wp_login_url() );

		$safe_login_url = wp_nonce_url( $login_url, 'switch_user' );

		$user = wp_get_current_user();

		$message = sprintf( __( 'You are logged in as %s.', 'user-toolkit' ), USRTK_UserTools()->user( $user->ID )->displayName() );
		$message .= ' <a href="' . $safe_login_url . '">' . sprintf( __( 'Switch back to %s', 'user-toolkit' ), USRTK_UserTools()->user( $user_from->ID )->displayName() ) . '</a>.';

		if ( ! is_admin() ) {
			echo '<div id="switch_back_user"><p>' . $message . '</p></div>';

			return;
		}

		echo '<div class="notice notice-warning is-dismissible"><p>' . $message . '</p></div>';

	}

	public function userProfileFields( $user ) {
		?>
        <tr>
            <th></th>
            <td>
				<?php
				if ( current_user_can( 'remove_users' ) || current_user_can( 'manage_network_users' ) ) {

					$login_url = add_query_arg( [
						'action'    => 'switch_user',
						'user_id'   => $user->ID,
						'user_from' => get_current_user_id(),
					], wp_login_url() );

					$safe_login_url = wp_nonce_url( $login_url, 'switch_user' );

					echo '<a href="' . $safe_login_url . '">' . __( 'Switch to', 'user-toolkit' ) . ' ' . $user->display_name . '</a>';

				}
				?>
            </td>
        </tr>
		<?php
	}

	public function restoreUserMenu( $wp_admin_bar ) {
		$user_id      = get_current_user_id();

		if ( ! $user_id ) {
			return;
		}

		if ( ! isset( $_COOKIE['user_from'] ) ) {
			return;
		}

		$user_from_id = $_COOKIE['user_from'];
		$user_from    = get_user_by( 'id', $user_from_id );

		if ( false === $user_from ) {
			return;
		}

		if ( $user_from->ID === get_current_user_id() ) {
			return;
		}

		$login_url = add_query_arg( [
			'action'    => 'restore_user',
			'user_id'   => $user_from_id,
			'user_from' => $user_id,
		], wp_login_url() );

		$safe_login_url = wp_nonce_url( $login_url, 'switch_user' );

		$wp_admin_bar->add_node(
			array(
				'parent' => 'user-actions',
				'id'     => 'restore-user',
				'title'  => sprintf( __( 'Switch back to %s', 'user-toolkit' ), USRTK_UserTools()->user( $user_from->ID )->displayName() ),
				'href'   => $safe_login_url,
			)
		);

	}


}