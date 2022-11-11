<?php

namespace UserToolkit;

class UserTools {

	private static $instance = null;

	public static function instance(): self {
		return self::$instance = self::$instance ?? new self();
	}

	public function init() {
		$this->hooks();
		$this->dependencies();
	}

	public function hooks() {
		add_action( 'admin_enqueue_scripts', [ $this, 'adminAssets' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'assets' ] );
	}

	public function user( $user_id ): User {
		return new User( $user_id );
	}


	public function dependencies() {

		$dependencies = [
			Admin::class,
			RestEndpoints::class,
			Authentication::class,
			UserSwitch::class,
		];

		foreach ( $dependencies as $dependency ) {
			( new $dependency )->init();
		}
	}

	function adminAssets() {
		wp_enqueue_script( 'user-toolkit', USRTK_URL . 'assets/dist/app.js', [ 'wp-api' ], USRTK_VERSION, true );
		wp_enqueue_style( 'user-toolkit', USRTK_URL . 'assets/dist/app.css', [], USRTK_VERSION );
	}

	function assets() {
		wp_enqueue_style( 'user-toolkit', USRTK_URL . 'assets/dist/app.css', [], USRTK_VERSION );
	}

}