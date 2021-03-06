<?php
/**
 * WooCommerce Memberships
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade WooCommerce Memberships to newer
 * versions in the future. If you wish to customize WooCommerce Memberships for your
 * needs please refer to http://docs.woothemes.com/document/woocommerce-memberships/ for more information.
 *
 * @package   WC-Memberships/Admin/Meta-Boxes
 * @author    SkyVerge
 * @category  Admin
 * @copyright Copyright (c) 2014-2015, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * User Membership Notes Meta Box
 *
 * @since 1.0.0
 */
class WC_Memberships_Meta_Box_User_Membership_Notes extends WC_Memberships_Meta_Box {


	/** @var string meta box id **/
	protected $id = 'wc-memberships-user-membership-notes';

	/** @var array list of supported screen IDs **/
	protected $screens = array( 'wc_user_membership' );


	/**
	 * Get the meta box title
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_title() {
		return __( 'Membership Notes', WC_Memberships::TEXT_DOMAIN );
	}


	/**
	 * Add meta box to the supported screen(s)
	 *
	 * @since 1.0.0
	 */
	public function add_meta_box() {
		global $pagenow;

		// Do not display on new membership screen
		if ( 'post-new.php' == $pagenow ) {
			return;
		}

		parent::add_meta_box();
	}


	/**
	 * Display the membership notes meta box
	 *
	 * @param WP_Post $post
	 * @since 1.0.0
	 */
	public function output( WP_Post $post ) {
		global $pagenow;

		// Prepare variables
		$user_membership = wc_memberships_get_user_membership( $post->ID );
		$user_id         = 'post.php' == $pagenow
								? $user_membership->get_user_id()
								: ( isset( $_GET['user'] ) ? $_GET['user'] : null );

		// Bail out if no user ID
		if ( ! $user_id ) {
			return;
		}

		$notes = $user_membership->get_notes();

		/**
		 * Fires at the beginning of the user membership notes meta box
		 *
		 * @since 1.0.0
		 * @param WC_Memberships_User_Membership $user_membership The user membership
		 */
		do_action( 'wc_memberships_before_user_membership_notes', $user_membership );

		?>
		<div class="wc-user-membership-add-note">
			<h4><?php esc_html_e( 'Add note', 'woocommerce' ); ?> <img class="help_tip" data-tip="<?php esc_attr_e( 'Add a note for your reference, or add a customer note (the user will be notified).', WC_Memberships::TEXT_DOMAIN ); ?>" src="<?php echo esc_url( WC()->plugin_url() ); ?>/assets/images/help.png" height="16" width="16" /></h4>
			<p>
				<textarea name="user_membership_note" id="user-membership-note" class="input-text" cols="100" rows="5"></textarea>
			</p>
			<p class="note-controls">
				<label>
					<input type="checkbox" name="notify_member" id="note-notify" class="notify-member" value="1" />
					<?php esc_html_e( 'Notify Member', WC_Memberships::TEXT_DOMAIN ); ?>
				</label>
				<a href="#" class="add-note js-add-note button"><?php esc_html_e( 'Add Note', WC_Memberships::TEXT_DOMAIN ); ?></a>
			</p>
		</div>
		<?php

		echo '<ul class="wc-user-membership-notes">';

		if ( $notes ) {

			foreach ( $notes as $note ) {

				$note_classes = get_comment_meta( $note->comment_ID, 'notified', true ) ? array( 'notified', 'note' ) : array( 'note' );

				include('views/html-membership-note.php');
			}

		} else {
			echo '<li>' . esc_html__( 'There are no notes yet.', WC_Memberships::TEXT_DOMAIN ) . '</li>';
		}

		echo '</ul>';

		/**
		 * Fires at the end of the user membership notes meta box
		 *
		 * @since 1.0.0
		 * @param WC_Memberships_User_Membership $user_membership The user membership
		 */
		do_action( 'wc_memberships_after_user_membership_notes', $user_membership );
	}

}
