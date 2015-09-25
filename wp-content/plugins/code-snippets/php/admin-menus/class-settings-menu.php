<?php

/**
 * This class handles the settings admin menu
 * @since 2.4.0
 * @package Code_Snippets
 */
class Code_Snippets_Settings_Menu extends Code_Snippets_Admin_Menu {

	/**
	 * Constructor
	 */
	public function __construct() {

		parent::__construct( 'settings',
			__( 'Settings', 'code-snippets' ),
			__( 'Snippets Settings', 'code-snippets' )
		);
	}

	function load() {
		parent::load();

		if ( is_network_admin() ) {
			wp_redirect( code_snippets_get_menu_url( 'settings', 'admin' ) );
			exit;
		}
	}

	/**
	 * Render the admin screen
	 */
	function render() {
		?>
		<div class="wrap">
			<h2><?php esc_html_e( 'Settings', 'code-snippets' ); ?></h2>

			<?php settings_errors( 'code-snippets-settings-notices' ); ?>

			<form action="<?php admin_url( 'options.php' ); ?>" method="post">
				<?php settings_fields( 'code-snippets' ); ?>
				<table class="form-table">
					<?php do_settings_sections( 'code-snippets' ); ?>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
