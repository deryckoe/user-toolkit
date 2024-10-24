<?php

namespace UserToolkit;

class UserSwitch {
	private $cookie_name = 'wp_usrtk_user_switch_ref';
	private $transient_prefix = 'user_switch_';

	public function init() {
		$this->actions();
	}

	public function actions() {
		add_filter( 'user_row_actions', [ $this, 'userRowAction' ], 10, 2 );
		add_action( 'init', [ $this, 'switchUser' ] );
		add_action( 'init', [ $this, 'handleCookies' ], 5 );
		add_action( 'admin_notices', [ $this, 'restoreUserNotice' ] );
		add_action( 'wp_footer', [ $this, 'restoreUserNotice' ] );
		add_action( 'admin_bar_menu', [ $this, 'restoreUserMenu' ], 10 );
		add_action( 'usrtk_after_profile_settings', [ $this, 'userProfileFields' ] );
	}

	private function generateSwitchKey( $user_from_id, $user_to_id ) {
		return wp_hash( $user_from_id . '|' . $user_to_id . '|' . time() . '|' . wp_salt( 'auth' ) );
	}

	private function storeSwitchData( $user_from_id, $user_to_id ) {
		$switch_key = $this->generateSwitchKey( $user_from_id, $user_to_id );

		$switch_data = [
			'user_from_id' => $user_from_id,
			'user_to_id'   => $user_to_id,
			'timestamp'    => time(),
			'key'          => $switch_key
		];

		set_transient( $this->transient_prefix . $switch_key, $switch_data, DAY_IN_SECONDS );

		update_option( '_user_switch_temp_key', [
			'key'     => $switch_key,
			'expires' => time() + DAY_IN_SECONDS
		] );

		if ( ! headers_sent() ) {
			setcookie(
				$this->cookie_name,
				$switch_key,
				[
					'expires'  => time() + DAY_IN_SECONDS,
					'path'     => COOKIEPATH,
					'domain'   => COOKIE_DOMAIN,
					'secure'   => is_ssl(),
					'httponly' => true,
					'samesite' => 'Strict'
				]
			);
		}

		$_COOKIE[ $this->cookie_name ] = $switch_key;

		return $switch_key;
	}

	public function handleCookies() {
		$temp_key = get_option( '_user_switch_temp_key' );

		if ( ! empty( $temp_key ) ) {
			if ( isset( $temp_key['delete'] ) ) {
				setcookie(
					$this->cookie_name,
					'',
					[
						'expires'  => time() - YEAR_IN_SECONDS,
						'path'     => COOKIEPATH,
						'domain'   => COOKIE_DOMAIN,
						'secure'   => is_ssl(),
						'httponly' => true,
						'samesite' => 'Strict'
					]
				);
				unset( $_COOKIE[ $this->cookie_name ] );
			} elseif ( isset( $temp_key['key'] ) ) {
				setcookie(
					$this->cookie_name,
					$temp_key['key'],
					[
						'expires'  => $temp_key['expires'],
						'path'     => COOKIEPATH,
						'domain'   => COOKIE_DOMAIN,
						'secure'   => is_ssl(),
						'httponly' => true,
						'samesite' => 'Strict'
					]
				);
				$_COOKIE[ $this->cookie_name ] = $temp_key['key'];
			}

			delete_option( '_user_switch_temp_key' );
		}
	}

	private function getSwitchData() {
		if ( ! isset( $_COOKIE[ $this->cookie_name ] ) ) {
			return false;
		}

		$switch_key = sanitize_text_field( wp_unslash( $_COOKIE[ $this->cookie_name ] ) );
		if ( empty( $switch_key ) ) {
			return false;
		}

		$switch_data = get_transient( $this->transient_prefix . $switch_key );

		if ( ! $switch_data || ! isset( $switch_data['key'] ) || $switch_data['key'] !== $switch_key ) {
			$this->clearSwitchData( $switch_key );

			return false;
		}

		return $switch_data;
	}

	private function clearSwitchData( $switch_key = null ) {
		if ( $switch_key === null && isset( $_COOKIE[ $this->cookie_name ] ) ) {
			$switch_key = sanitize_text_field( wp_unslash( $_COOKIE[ $this->cookie_name ] ) );
		}

		if ( $switch_key ) {
			delete_transient( $this->transient_prefix . $switch_key );
		}

		setcookie(
			$this->cookie_name,
			'',
			time() - YEAR_IN_SECONDS,
			COOKIEPATH,
			COOKIE_DOMAIN,
			is_ssl(),
			true
		);

		unset( $_COOKIE[ $this->cookie_name ] );
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
		], admin_url( 'users.php' ) );

		$safe_login_url = wp_nonce_url( $login_url, 'switch_user' );

		$switch = '<a href="' . esc_url( $safe_login_url ) . '">' . __( 'Switch to', 'user-toolkit' ) . '</a>';

		return array_merge( $actions, [ 'switch' => $switch ] );
	}

	public function switchUser() {
		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : false;

		if ( ! $action || ! in_array( $action, [ 'switch_user', 'restore_user' ] ) ) {
			return;
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'switch_user' ) ) {
			wp_die( esc_html__( 'Invalid security token sent.', 'user-toolkit' ) );
		}

		$user_id      = isset( $_GET['user_id'] ) ? intval( $_GET['user_id'] ) : 0;
		$user_from_id = isset( $_GET['user_from'] ) ? intval( $_GET['user_from'] ) : 0;

		if ( ! $user_id || ! $user_from_id ) {
			wp_die( esc_html__( 'Invalid user ID.', 'user-toolkit' ) );
		}

		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			wp_die( esc_html__( 'Invalid user.', 'user-toolkit' ) );
		}

		if ( $action === 'restore_user' ) {
			$switch_data = $this->getSwitchData();
			if ( ! $switch_data ) {
				wp_die( esc_html__( 'Switch data not found.', 'user-toolkit' ) );
			}

			if ( $switch_data['user_to_id'] !== get_current_user_id() ) {
				wp_die( esc_html__( 'Invalid switch back attempt.', 'user-toolkit' ) );
			}

			if ( $switch_data['user_from_id'] !== $user_id ) {
				wp_die( esc_html__( 'Invalid switch back target.', 'user-toolkit' ) );
			}
		}

		if ( $action === 'switch_user' ) {
			if ( ! current_user_can( 'remove_users' ) && ! current_user_can( 'manage_network_users' ) ) {
				wp_die( esc_html__( 'You do not have permission to switch users.', 'user-toolkit' ) );
			}

			$this->storeSwitchData( get_current_user_id(), $user_id );
		}

		wp_clear_auth_cookie();
		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID );

		if ( $action === 'restore_user' ) {
			$this->clearSwitchData();
		}

		wp_safe_redirect( admin_url() );
		exit;
	}

	public function restoreUserNotice() {
		$switch_data = $this->getSwitchData();
		if ( ! $switch_data ) {
			return;
		}

		$user_from = get_user_by( 'id', $switch_data['user_from_id'] );
		if ( ! $user_from || $user_from->ID === get_current_user_id() ) {
			return;
		}

		$login_url = add_query_arg( [
			'action'    => 'restore_user',
			'user_id'   => $user_from->ID,
			'user_from' => get_current_user_id(),
		], admin_url( 'index.php' ) );

		$safe_login_url = wp_nonce_url( $login_url, 'switch_user' );
		$user           = wp_get_current_user();

		$message = sprintf(
		/* translators: %s: Original user's display name */
			__( 'You are logged in as %s.', 'user-toolkit' ),
			USRTK_UserTools()->user( $user->ID )->displayName()
		);
		$message .= ' <a href="' . esc_url( $safe_login_url ) . '">' .
		            sprintf(
		            /* translators: %s: Current user's display name */
			            __( 'Switch back to %s', 'user-toolkit' ),
			            USRTK_UserTools()->user( $user_from->ID )->displayName()
		            ) . '</a>.';

		if ( ! is_admin() ) {
			echo '<div id="switch_back_user"><p>' . wp_kses_post( $message ) . '</p></div>';

			return;
		}

		echo '<div class="notice notice-warning is-dismissible"><p>' . wp_kses_post( $message ) . '</p></div>';
	}

	public function restoreUserMenu( $wp_admin_bar ) {
		if ( ! is_admin_bar_showing() ) {
			return;
		}

		$switch_data = $this->getSwitchData();
		if ( ! $switch_data ) {
			return;
		}

		$user_from = get_user_by( 'id', $switch_data['user_from_id'] );
		if ( ! $user_from || $user_from->ID === get_current_user_id() ) {
			return;
		}

		$login_url = add_query_arg( [
			'action'    => 'restore_user',
			'user_id'   => $user_from->ID,
			'user_from' => get_current_user_id(),
		], admin_url( 'index.php' ) );

		$safe_login_url = wp_nonce_url( $login_url, 'switch_user' );

		$wp_admin_bar->add_node( [
			'parent' => 'user-actions',
			'id'     => 'restore-user',
			'title'  => sprintf(
			/* translators: %s: Original user's display name */
				__( 'Switch back to %s', 'user-toolkit' ),
				USRTK_UserTools()->user( $user_from->ID )->displayName()
			),
			'href'   => $safe_login_url,
		] );
	}

	public function userProfileFields( $user ) {
		if ( ! current_user_can( 'remove_users' ) && ! current_user_can( 'manage_network_users' ) ) {
			return;
		}

		$login_url = add_query_arg( [
			'action'    => 'switch_user',
			'user_id'   => $user->ID,
			'user_from' => get_current_user_id(),
		], admin_url( 'users.php' ) );

		$safe_login_url = wp_nonce_url( $login_url, 'switch_user' );
		?>
        <tr>
            <th></th>
            <td>
                <a href="<?php echo esc_url( $safe_login_url ); ?>">
					<?php echo esc_html__( 'Switch to', 'user-toolkit' ) . ' ' . esc_html( $user->display_name ); ?>
                </a>
            </td>
        </tr>
		<?php
	}
}