<?php

namespace UserTools;

class RestEndpoints {

	public function init() {
		$this->actions();
	}

	public function actions() {
		add_action( 'rest_api_init', [ $this, 'registerCanLoginField' ] );
	}


	function registerCanLoginField() {
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


}