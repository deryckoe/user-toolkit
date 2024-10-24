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
		add_action( 'pre_get_users', [ $this, 'sortColumns' ] );
		add_action( 'manage_users_extra_tablenav', [ $this, 'columnFilters' ] );
		add_action( 'pre_get_users', [ $this, 'filterColumns' ] );
		add_action( 'edit_user_profile', [ $this, 'userProfileFields' ] );
		add_action( 'show_user_profile', [ $this, 'userProfileFields' ] );
		add_action( 'personal_options_update', [ $this, 'saveUserFields' ] );
		add_action( 'edit_user_profile_update', [ $this, 'saveUserFields' ] );
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

				if ( $user_id === get_current_user_id() || $user_id === 1 ) {
					$active_label = ( $active === 1 ) ? __( 'On', 'user-toolkit' ) : __( 'Off', 'user-toolkit' );

					return '<div class="ut-readonly-toggle" data-active="' . $active . '">' . $active_label . '</div>';
				}

				return '<div class="ut-toggle" data-active="' . $active . '" data-user-id="' . $user_id . '">
                            <div class="ut-switch"></div>
                        </div>';

			case 'registered':
				return USRTK_UserTools()->user( $user_id )->registered();
			case 'id':
				return $user_id;
		}

		return $value;
	}

	public function columnSortable( $columns ): array {
		return array_merge( $columns, [
			'last_login' => 'last_login',
			'registered' => 'registered',
			'id'         => 'id'
		] );
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

	public function columnFilters( $which ) {

		if ( $which === 'top' ) {

			if ( ! isset( $_REQUEST['_nonce_user_toolkit_filter'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_nonce_user_toolkit_filter'] ) ), 'user_toolkit_filter' ) ) {
				$can_login  = '';
				$last_login = 'all-time';
			} else {
				$can_login  = isset( $_GET['can_login'] ) ? sanitize_text_field( wp_unslash( $_GET['can_login'] ) ) : '';
				$last_login = isset( $_GET['last_login'] ) ? sanitize_text_field( wp_unslash( $_GET['last_login'] ) ) : 'all-time';
			}

			$can_login_label = ( in_array( $can_login, [
				'',
				'-1'
			] ) ) ? __( 'Login status', 'user-toolkit' ) : __( 'All', 'user-toolkit' );
			?>
            <div class="alignleft actions">
				<?php wp_nonce_field( 'user_toolkit_filter', '_nonce_user_toolkit_filter' ); ?>
                <label class="screen-reader-text"
                       for="can_login"><?php esc_html_e( 'All login status', 'user-toolkit' ) ?></label>
                <select name="can_login" id="can_login">
                    <option value="-1"><?php echo esc_html( $can_login_label ) ?></option>
                    <option value="1" <?php selected( $can_login, 1 ) ?>><?php esc_html_e( 'Enabled (Active)', 'user-toolkit' ) ?></option>
                    <option value="0"<?php selected( $can_login, 0 ) ?>><?php esc_html_e( 'Disabled', 'user-toolkit' ) ?></option>
                </select>
                <label class="screen-reader-text"
                       for="can_login"><?php esc_html_e( 'Login date range', 'user-toolkit' ) ?></label>
                <select name="last_login" id="last_login">
                    <option value="all-time"><?php esc_html_e( 'All Logins', 'user-toolkit' ) ?></option>
                    <option value="never" <?php selected( $last_login, 'never' ) ?>><?php esc_html_e( "Not yet logged in", 'user-toolkit' ) ?></option>
                    <option value="today" <?php selected( $last_login, 'today' ) ?>><?php esc_html_e( "Today's logins", 'user-toolkit' ) ?></option>
                    <option value="last-30" <?php selected( $last_login, 'last-30' ) ?>><?php esc_html_e( 'Last 30 days logins', 'user-toolkit' ) ?></option>
                    <option value="last-60" <?php selected( $last_login, 'last-60' ) ?>><?php esc_html_e( 'Last 60 days logins', 'user-toolkit' ) ?></option>
                </select>
                <input type="submit" class="button action" value="<?php esc_html_e( 'Filter', 'user-toolkit' ) ?>">

            </div>
			<?php
		}
	}

	public function filterColumns( $query ): void {
		if ( ! is_admin() ) {
			return;
		}

		global $pagenow;

		if ( 'users.php' !== $pagenow ) {
			return;
		}

		if ( ! isset( $_REQUEST['_nonce_user_toolkit_filter'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_nonce_user_toolkit_filter'] ) ), 'user_toolkit_filter' ) ) {
			return;
		}


		$meta_query = $query->get( 'meta_query' ) ?: [];

		$can_login = isset( $_GET['can_login'] ) ? sanitize_text_field( wp_unslash( $_GET['can_login'] ) ) : '';

		if ( in_array( $can_login, [ '0', '1' ] ) ) {
			$meta_query['relation'] = 'AND';
			$meta_query[]           = [
				'key'     => 'can_login',
				'value'   => $can_login,
				'compare' => '='
			];
		}

		$last_login = isset( $_GET['last_login'] ) ? sanitize_text_field( wp_unslash( $_GET['last_login'] ) ) : '';

		if ( ! empty( $last_login ) ) {
			$date_to = strtotime( "tomorrow midnight" );

			if ( $last_login === 'today' ) {
				$date_from = strtotime( "today midnight" );
			}

			if ( $last_login === 'last-30' ) {
				$date_from = strtotime( "-30 days midnight" );
			}

			if ( $last_login === 'last-60' ) {
				$date_from = strtotime( "-60 days midnight" );
			}

			if ( ! empty( $date_from ) ) {
				$meta_query['relation'] = 'AND';
				$meta_query[]           = [
					'key'     => 'last_login',
					'value'   => [ $date_from, $date_to ],
					'compare' => 'between'
				];
			}

			if ( $last_login === 'never' ) {
				$meta_query[] = [
					'relation' => 'OR',
					[
						'key'   => 'last_login',
						'value' => '',
					],
					[
						'key'     => 'last_login',
						'compare' => 'NOT EXISTS',
					]
				];
			}
		}

		$query->set( 'meta_query', $meta_query );

	}

	public function userProfileFields( $user ) {
		?>
        <h2><?php esc_html_e( 'User Tools', 'user-toolkit' ) ?></h2>
        <table class="form-table">
			<?php do_action( 'usrtk_before_profile_settings', $user ); ?>
			<?php if ( current_user_can( 'edit_user' ) && $user->ID !== get_current_user_id() ) : ?>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Login active', 'user-toolkit' ) ?></th>
                    <td>
                        <div class="time_wrapper">
                            <label for="can_login">
								<?php $disabled = ( $user->ID === 1 ) ? ' disabled ' : '' ?>
                                <input name="can_login" type="checkbox" id="can_login"
									<?php echo esc_attr( $disabled ) ?>
                                       value="1" <?php checked( USRTK_UserTools()->user( $user->ID )->canLogin(), 1 ) ?>>
								<?php esc_html_e( 'Activate user login', 'user-toolkit' ) ?></label>
							<?php if ( $disabled ) : ?>
                                <p class="description"><?php esc_html_e( 'First created user cannot be disabled.', 'user-toolkit' ) ?></p>
							<?php endif; ?>
                        </div>
                    </td>
                </tr>
			<?php endif; ?>
            <tr>
                <th scope="row"><label><?php esc_html_e( 'Registered', 'user-toolkit' ) ?></label></th>
                <td>
                    <div class="time_wrapper">
						<?php echo wp_kses_post( USRTK_UserTools()->user( $user->ID )->registered() ); ?>
                    </div>
                </td>
            </tr>
            <tr>
                <th scope="row"><label><?php esc_html_e( 'Last login', 'user-toolkit' ) ?></label></th>
                <td>
                    <div class="time_wrapper">
						<?php echo wp_kses_post( USRTK_UserTools()->user( $user->ID )->lastLogin() ); ?>
                    </div>
                </td>
            </tr>
			<?php do_action( 'usrtk_after_profile_settings', $user ); ?>
        </table>
		<?php
	}

	public function saveUserFields( $user_id ): bool {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}

		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'update-user_' . $user_id ) ) {
			return false;
		}

		$can_login = isset( $_POST['can_login'] ) ? sanitize_text_field( wp_unslash( $_POST['can_login'] ) ) : '0';

		if ( $user_id === 1 || $user_id === get_current_user_id() ) {
			$can_login = '1';
		}

		if ( ! in_array( $can_login, [ '0', '1' ] ) ) {
			return false;
		}

		update_user_meta( $user_id, 'can_login', $can_login );

		return true;
	}
}