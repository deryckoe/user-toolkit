<?php

namespace UserToolkit;

class RestEndpoints {

	public function init() {
		$this->actions();
	}

	public function actions() {
		add_action( 'rest_api_init', [ $this, 'registerCanLoginField' ] );
		add_action( 'rest_api_init', [ $this, 'registerLastLoginField' ] );
		add_filter( 'rest_user_collection_params', [ $this, 'registerParams' ] );
		add_filter( 'rest_user_query', [ $this, 'filterByParams' ], 10, 2 );
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
						'validate_callback' => function ( $value, \WP_REST_Request $request ) {

							if ( (int) $request->get_param( 'id' ) === 1 ) {
								return false;
							}

							return in_array( $value, [ 0, 1 ] );
						},
					],
				]
			]
		);
	}

	function registerLastLoginField() {
		register_rest_field(
			'user',
			'last_login',
			[
				'get_callback'    => function ( $user ) {
					$epoch_date = get_user_meta( $user['id'], 'last_login', true );

					return ( ! empty( $epoch_date ) ) ? gmdate( 'c', $epoch_date ) : null;
				},
				'update_callback' => function ( $value, $user, $field_name ) {
					$epoch_date = strtotime( $value );

					return update_user_meta( $user->id, $field_name, $epoch_date );
				},
				'schema'          => [
					'type'        => 'number',
					'arg_options' => [
						'sanitize_callback' => function ( $value ) {
							return sanitize_text_field( $value );
						},
						'validate_callback' => function ( $value, \WP_REST_Request $request ) {
							return ( ! empty( strtotime( $value ) ) );
						},
					],
				]
			]
		);
	}

	public function registerParams( $params ) {
		$params['last_login']   = [
			'description' => 'Last login range',
			'type'        => 'string',
		];

		return $params;
	}

	public function filterByParams( $prepared_args, \WP_REST_Request $request ) {
		$last_login = $request->get_param( 'last_login' );
		if ( ! empty( $last_login ) ) {

			$last_login_range = explode( ',', $last_login );

			if ( count( $last_login_range ) === 1 ) {
				$last_login_range[1] = gmdate( 'Y-m-d\TH:i:s', strtotime( 'now' ) );
			}

			$last_login_args = array_map( function ( $date ) {
				return strtotime( $date );
			}, $last_login_range );

			$prepared_args['meta_query'] = [
				[
					'key'     => 'last_login',
					'value'   => $last_login_args,
					'compare' => 'BETWEEN',
				],
			];
		}

		return $prepared_args;
	}


}