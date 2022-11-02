<?php

namespace UserToolkit;

class UserTools {

	private static ?self $instance = null;

	public function __construct() {

	}

	public static function instance(): self {
		return self::$instance = self::$instance ?? new self();
	}

	public function init() {
		$this->hooks();
		$this->dependencies();
	}

	public function hooks() {
		add_action( 'admin_enqueue_scripts', [ $this, 'assets' ] );
	}

	public function user( $user_id ): User {
		return new User( $user_id );
	}


	public function dependencies() {

		$dependencies = [
			Admin::class,
			RestEndpoints::class,
			Authentication::class,
		];

		foreach ( $dependencies as $dependency ) {
			( new $dependency )->init();
		}
	}

	function assets() {
		wp_enqueue_script( 'user-toolkit', UT_URL . 'assets/dist/app.js', [ 'wp-api' ], UT_VERSION, true );
		wp_enqueue_style( 'user-toolkit', UT_URL . 'assets/dist/app.css', [], UT_VERSION );
	}

}