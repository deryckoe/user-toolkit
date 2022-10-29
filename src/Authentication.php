<?php

namespace UserTools;

class Authentication {

	public function init() {
		$this->actions();
	}

	public function actions() {
		add_filter( 'authenticate', [ $this, 'validate' ], 30 );
		add_action( 'user_register', [ $this, 'registerUserMeta' ] );
		add_action( 'wp_login', [ $this, 'registerLastLogin' ], 10, 2 );
	}

	function validate( $user ) {

		if ( ! $user instanceof \WP_User ) {
			return $user;
		}

		$can_login = ut_can_user_login( $user->ID );

		if ( $can_login != 1 ) {
			$user = new \WP_Error( 'authentication_deactivated', __( '<strong>Error</strong>: Username is deactivated. Please contact your manager for further information.', 'user-tools' ) );
		}

		return $user;
	}

	public function registerUserMeta( $user_id ) {
		$value = get_option( 'ut_can_login', '1' );
		update_user_meta( $user_id, 'can_login', $value );
	}

	function registerLastLogin( $user_login, $user ): void {
		update_user_meta( $user->ID, 'last_login', current_time( 'timestamp', true ) );
	}

}