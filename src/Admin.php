<?php

namespace UserTools;

use function UserTools;

class Admin {

	public function init() {
		$this->actions();
	}

	public function actions() {
		add_filter( 'manage_users_columns', [ $this, 'columnHeaders' ] );
		add_filter( 'manage_users_custom_column', [ $this, 'columnContent' ], 10, 3 );
		add_filter( 'manage_users_sortable_columns', [ $this, 'columnSortable' ] );
		add_action( 'pre_get_posts', [ $this, 'sortColumns' ] );
		add_action( 'manage_users_extra_tablenav', [ $this, 'columnFilters' ] );
		add_action( 'pre_get_users', [ $this, 'filterColumns' ] );
		add_action( 'edit_user_profile', [ $this, 'userProfileFields' ] );
		add_action( 'show_user_profile', [ $this, 'userProfileFields' ] );
		add_action( 'personal_options_update', [ $this, 'saveUserFields' ] );
		add_action( 'edit_user_profile_update', [ $this, 'saveUserFields' ] );
	}

	function columnHeaders( $columns ): array {
		return array_merge( $columns, [
			'can_login'  => __( 'Active', 'user-tools' ),
			'last_login' => __( 'Last login', 'user-tools' ),
			'registered' => __( 'Registered', 'user-tools' ),
			'id'         => __( 'ID', 'user-tools' ),
		] );
	}

	function columnContent( $value, $column, $user_id ) {

		switch ( $column ) {
			case 'last_login':
                return UserTools()->user( $user_id )->lastLogin();

			case 'can_login':
                $active = UserTools()->user( $user_id )->canLogin();

				return <<<HTML
					<div class="ut-toggle" data-active="$active" data-user-id="$user_id">
						<div class="switch"></div>
					</div>
				HTML;

			case 'registered':
                return UserTools()->user( $user_id )->registered();
			case 'id':

				return $user_id;
		}

		return $value;

	}

	public function columnSortable( $columns ): array {
		return array_merge( $columns, [ 'last_login' => 'last_login', 'registered' => 'registered', 'id' => 'id' ] );
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

		$can_login = $_GET['can_login'] ?? '';
		$all_label = isset( $_GET['can_login'] ) && $_GET['can_login'] !== '-1' ? __( 'All', 'user-tools' ) : __( 'Login status', 'user-tools' )

		?>

        <div class="alignleft actions">
            <form method="get">
                <label class="screen-reader-text" for="can_login"><?php _e( 'All login status', 'user-tools' ) ?></label>
                <select name="can_login" id="can_login">
                    <option value="-1"><?php echo $all_label ?></option>
                    <option value="1" <?php selected( $can_login, 1 ) ?>><?php _e( 'Enabled (Active)', 'user-tools' ) ?></option>
                    <option value="0"<?php selected( $can_login, 0 ) ?>><?php _e( 'Disabled', 'user-tools' ) ?></option>
                </select>
                <input type="submit" class="button action" value="<?php _e('Filter' ) ?>">
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

		if ( isset( $_GET['can_login'] ) && ! in_array( $_GET['can_login'], [ '0', '1' ] ) ) {
			return;
		}

		if ( ! isset( $_GET['can_login'] ) ) {
			return;
		}

		$meta_query = [
			[
				'key'     => 'can_login',
				'value'   => $_GET['can_login'],
				'compare' => '='
			]
		];

		$query->set( 'meta_query', $meta_query );
	}


	public function userProfileFields( $user ) {
		?>
        <h2><?php _e( 'User Tools', 'user-tools' ) ?></h2>
        <table class="form-table">
			<?php if ( current_user_can( 'edit_user' ) ) : ?>
                <tr>
                    <th scope="row"><?php _e( 'Login active', 'user-tools' ) ?></th>
                    <td>
                        <div class="time_wrapper">
                            <label for="can_login">
                                <input name="can_login" type="checkbox" id="can_login"
                                       value="1" <?php checked( UserTools()->user( $user->ID )->canLogin(), 1 ) ?>>
								<?php _e( 'Activate user login', 'user-tools' ) ?></label>
                        </div>
                    </td>
                </tr>
			<?php endif; ?>
            <tr>
                <th scope="row"><label><?php _e( 'Registered', 'user-tools' ) ?></label></th>
                <td>
                    <div class="time_wrapper">
						<?php echo UserTools()->user($user->ID)->registered(); ?>
                    </div>
                </td>
            </tr>
            <tr>
                <th scope="row"><label><?php _e( 'Last login', 'user-tools' ) ?></label></th>
                <td>
                    <div class="time_wrapper">
						<?php echo UserTools()->user($user->ID)->lastLogin(); ?>
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

		$value = $_POST['can_login'] ?? '0';

		update_user_meta( $user_id, 'can_login', $value );

		return true;
	}


}