<?php

namespace UserToolkit;

class User {

	/* @var int */
	private $user_id;

	public function __construct( $user_id ) {
		$this->user_id = $user_id;
	}

	public function lastLogin(): ?string {
		$last_login_timestamp = get_user_meta( $this->user_id, 'last_login', true );

		if ( empty( $last_login_timestamp ) ) {
			$when_last_login = get_user_meta( $this->user_id, 'when_last_login', true );
			update_user_meta( $this->user_id, 'last_login', $when_last_login );
			$last_login_timestamp = get_user_meta( $this->user_id, 'last_login', true );
		}

		if ( empty( $last_login_timestamp ) ) {
			$_um_last_login = get_user_meta( $this->user_id, '_um_last_login', true );
			update_user_meta( $this->user_id, 'last_login', $_um_last_login );
			$last_login_timestamp = get_user_meta( $this->user_id, 'last_login', true );
		}

		$date = wp_date( USRTK_DATE_FORMAT, $last_login_timestamp, wp_timezone() );
		$time = wp_date( USRTK_TIME_FORMAT, $last_login_timestamp, wp_timezone() );

		$last_login = __( 'Never', 'user-toolkit' );

		if ( ! empty( $last_login_timestamp ) ) {
			$last_login = '<span class="time_formatted">' . sprintf( __( '%s at %s', 'user-toolkit' ), $date, $time ) . '</span>';
			$human      = human_time_diff( $last_login_timestamp, current_time( 'timestamp', true ) );
			$last_login .= '<br><span class="time_diff">' . sprintf( __( '%s ago', 'user-toolkit' ), $human ) . '</span>';
		}

		return $last_login;
	}

	public function registered(): string {
		$user = get_user_by( 'id', $this->user_id );

		$date = wp_date( USRTK_DATE_FORMAT, strtotime( $user->user_registered ), wp_timezone() );
		$time = wp_date( USRTK_TIME_FORMAT, strtotime( $user->user_registered ), wp_timezone() );

		$registered = wp_kses_post(
			sprintf(
				'<span class="time_formatted">' . esc_html__( '%s at %s', 'user-toolkit' ) . '</span>',
				esc_html($date),
				esc_html($time)
			)
		);
		$human      = human_time_diff( strtotime( $user->user_registered ), current_time( 'timestamp', true ) );
		$registered .= wp_kses_post(
			sprintf(
				'<br><span class="time_diff">' . esc_html__( '%s ago', 'user-toolkit' ) . '</span>',
				esc_html($human)
			)
		);

		return $registered;
	}

	public function canLogin(): int {
		$can_login = get_user_meta( $this->user_id, 'can_login', true );

		return ( $can_login === '' || $can_login === '1' ) ? 1 : 0;
	}

	public function displayName() {

		$user = get_user_by( 'id', $this->user_id );

		$display_name = $user->display_name;

		if ( ! empty( $user->first_name ) ) {
			$display_name = $user->first_name . ' ' . $user->last_name;
		}

		return trim( $display_name );

	}

}