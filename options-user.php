<?php
// functions for managing Shibboleth user options through the WordPress administration panel

add_action('profile_personal_options', 'shibboleth_profile_personal_options');
add_action('personal_options_update', 'shibboleth_personal_options_update');
add_action('show_user_profile', 'shibboleth_show_user_profile');
add_action('admin_footer-user-edit.php', 'shibboleth_admin_footer_edit_user');

/**
 * For WordPress accounts that were created by Shibboleth, limit what profile
 * attributes they can modify.
 *
 * @since 1.3
 */
function shibboleth_profile_personal_options() {
	$user = wp_get_current_user();

	if (get_user_meta( $user->ID, 'shibboleth_account') ) {

		add_filter( 'show_password_fields', create_function( '$v', 'return false;' ) );

		add_action( 'admin_footer-profile.php', 'shibboleth_admin_footer_profile' );
	}
}

/**
 * For WordPress accounts that were created by Shibboleth, disable certain fields
 * that they are allowed to modify.
 *
 * @since 1.3
 */
function shibboleth_admin_footer_profile() {
	$managed_fields = shibboleth_get_managed_user_fields();

	if ( ! empty( $managed_fields ) ) {
		$selectors = join( ',', array_map( create_function( '$a', 'return "#$a";' ), $managed_fields ) );

		echo '
		<script type="text/javascript">
			jQuery(function() {
				jQuery("' . $selectors . '").attr("disabled", true);
				jQuery("#first_name").parents(".form-table").before("<div class=\"updated fade\"><p>'
					. __( 'Some profile fields cannot be changed from WordPress.', 'shibboleth' ) . '</p></div>");
				jQuery("form#your-profile").submit(function() {
					jQuery("' . $selectors . '").attr("disabled", false);
				});
			});
		</script>';
	}
}


/**
 * For WordPress accounts that were created by Shibboleth, warn the admin of
 * Shibboleth managed attributes.
 *
 * @since 1.3
 */
function shibboleth_admin_footer_edit_user() {
	global $user_id;

	if ( get_user_meta( $user_id, 'shibboleth_account' ) ) {
		$shibboleth_fields = array();

		$shibboleth_fields = array_merge( $shibboleth_fields, shibboleth_get_managed_user_fields() );

		$update = shibboleth_getoption( 'shibboleth_update_roles' );

		if ( $update ) {
			$shibboleth_fields = array_merge( $shibboleth_fields, array('role') );
		}

		if ( ! empty( $shibboleth_fields ) ) {
			$selectors = array();

			foreach( $shibboleth_fields as $field ) {
				$selectors[] = 'label[for=\'' . $field . '\']';
			}

			echo '
			<script type="text/javascript">
				jQuery(function() {
					jQuery("' . implode( ',', $selectors ) . '").before("<span style=\"color: #F00; font-weight: bold;\">*</span> ");
					jQuery("#first_name").parents(".form-table")
						.before("<div class=\"updated fade\"><p><span style=\"color: #F00; font-weight: bold;\">*</span> '
						. __( 'Starred fields are managed by Shibboleth and should not be changed from WordPress.', 'shibboleth' ) . '</p></div>");
				});
			</script>';
		}
	}
}


/**
 * Add change password link to the user profile for Shibboleth users.
 *
 * @since 1.3
 */
function shibboleth_show_user_profile() {
	$user = wp_get_current_user();
	$password_change_url = shibboleth_getoption( 'shibboleth_password_change_url' );

	if ( get_user_meta( $user->ID, 'shibboleth_account' ) && ! empty( $password_change_url ) ) {
?>
	<table class="form-table">
		<tr>
			<th><?php _e( 'Change Password', 'shibboleth' ) ?></th>
			<td><a href="<?php echo esc_url( $password_change_url ); ?>" rel="nofollow" target="_blank"><?php
				_e( 'Change your password', 'shibboleth' ); ?></a></td>
		</tr>
	</table>
<?php
	}
}


/**
 * Ensure profile data isn't updated by the user.  This only applies to accounts that were
 * provisioned through Shibboleth, and only for those user fields marked as 'managed'.
 *
 * @since 1.3
 */
function shibboleth_personal_options_update() {
	$user = wp_get_current_user();

	if ( get_user_meta( $user->ID, 'shibboleth_account' ) ) {
		$managed = shibboleth_get_managed_user_fields();

		if ( in_array( 'first_name', $managed ) ) {
			add_filter( 'pre_user_first_name', create_function( '$n', 'return $GLOBALS["current_user"]->first_name;' ) );
		}

		if ( in_array( 'last_name', $managed ) ) {
			add_filter( 'pre_user_last_name', create_function( '$n', 'return $GLOBALS["current_user"]->last_name;' ) );
		}

		if ( in_array( 'nickname', $managed ) ) {
			add_filter( 'pre_user_nickname', create_function( '$n', 'return $GLOBALS["current_user"]->nickname;' ) );
		}

		if ( in_array( 'display_name', $managed ) ) {
			add_filter( 'pre_user_display_name', create_function( '$n', 'return $GLOBALS["current_user"]->display_name;' ) );
		}

		if ( in_array( 'email', $managed ) ) {
			add_filter( 'pre_user_email', create_function( '$e', 'return $GLOBALS["current_user"]->user_email;' ) );
		}
	}
}

/**
 * Adds a button to user profile pages if administrator has allowed
 * users to manually combine accounts.
 *
 * @param object $user WP_User object
 * @since 1.9
 */
function shibboleth_link_accounts_button( $user ) {
	$allowed = shibboleth_getoption( 'shibboleth_manually_combine_accounts', 'disallow' );

	if ( $allowed === 'allow' || $allowed === 'bypass' ) {
		$linked = get_user_meta( $user->ID, 'shibboleth_account', true ); ?>
		<table class="form-table">
			<tr>
				<th><label for="link_shibboleth"><?php _e( 'Link Shibboleth Account', 'shibboleth' ); ?></label></th>
				<td>
					<?php if ( $linked ) { ?>
						<button type="button" disabled class="button"><?php _e( 'Link Shibboleth Account', 'shibboleth' ); ?></button>
						<p class="description"><?php _e('Your account is already linked to Shibboleth.', 'shibboleth' ); ?></p>
					<?php } else { ?>
						<a href="?shibboleth=link"><button type="button" class="button"><?php _e( 'Link Shibboleth Account', 'shibboleth' ); ?></button></a>
						<p class="description"><?php _e('Your account has not been linked to Shibboleth. To link your account, click the button above.', 'shibboleth' ); ?></p>
					<?php } ?>
				</td>
			</tr>
		</table>
	<?php }
}
add_action( 'show_user_profile', 'shibboleth_link_accounts_button' );
add_action( 'edit_user_profile', 'shibboleth_link_accounts_button' );

/**
 * Processes the linking of a user's account if administrator has allowed
 * users to manually combine accounts and redirects them to an admin notice.
 *
 * @since 1.9
 */
function shibboleth_link_accounts() {
	$screen = get_current_screen();

	if ( is_admin() && $screen->id == 'profile' ) {
		$user_id = get_current_user_id();

		// If profile page has ?shibboleth=link action and current user can edit their profile, proceed
		if ( isset( $_GET['shibboleth'] ) && $_GET['shibboleth'] === 'link' && current_user_can( 'edit_user', $user_id ) ) {
			$shib_logging = shibboleth_getoption( 'shibboleth_logging', false, true );
			$allowed = shibboleth_getoption( 'shibboleth_manually_combine_accounts', 'disallow' );

			// If user's account is not already linked with shibboleth, proceed
			if ( ! get_user_meta( $user_id, 'shibboleth_account' ) ) {
				// If manual account merging is enabled, proceed
				if ( $allowed === 'allow' || $allowed === 'bypass' ) {
					// If there is an existing shibboleth session, proceed
					if ( shibboleth_session_active() ) {
						$shib_headers = shibboleth_getoption( 'shibboleth_headers', false, true );

						$username = shibboleth_getenv( $shib_headers['username']['name'] );
						$email = shibboleth_getenv( $shib_headers['email']['name'] );

						$user = get_user_by( 'id', $user_id );

						// If username and email match, safe to merge
						if ( $user->user_login === $username && strtolower( $user->user_email ) === strtolower( $email ) ) {
							update_user_meta( $user->ID, 'shibboleth_account', true );
							if ( in_array( 'account_merge', $shib_logging ) || defined( 'WP_DEBUG' ) && WP_DEBUG ) {
								error_log( '[Shibboleth WordPress Plugin Logging] SUCCESS: User ' . $user->user_login . ' (ID: ' . $user->ID . ') merged accounts manually.' );
							}
							wp_safe_redirect( get_edit_user_link() . '?shibboleth=linked' );
							exit;
						// If username matches, check if there is a conflict with the email
						} elseif ( $user->user_login === $username ) {
								$prevent_conflict = get_user_by( 'email', $email );
								// If username matches and there is no existing account with the email, safe to merge
								if ( ! $prevent_conflict->ID ) {
									update_user_meta( $user->ID, 'shibboleth_account', true );
									if ( in_array( 'account_merge', $shib_logging ) || defined( 'WP_DEBUG' ) && WP_DEBUG ) {
										error_log( '[Shibboleth WordPress Plugin Logging] SUCCESS: User ' . $user->user_login . ' (ID: ' . $user->ID . ') merged accounts manually.' );
									}
									wp_safe_redirect( get_edit_user_link() . '?shibboleth=linked' );
									exit;
								// If username matches and there is an existing account with the email, fail
								} else {
									if ( in_array( 'account_merge', $shib_logging ) || defined( 'WP_DEBUG' ) && WP_DEBUG ) {
										error_log( '[Shibboleth WordPress Plugin Logging] ERROR: User ' . $user->user_login . ' (ID: ' . $user->ID . ') failed to manually merge accounts. Reason: An account already exists with the email: ' . $email . ' .' );
									}
									wp_safe_redirect( get_edit_user_link() . '?shibboleth=failed' );
									exit;
								}
						// If email matches and username bypass is enabled, check if there is a conflict with the username
						} elseif ( strtolower( $user->user_email) === strtolower( $email ) && $allowed === 'bypass' ) {
							$prevent_conflict = get_user_by( 'user_login', $username );
							// If email matches and there is no existing account with the username, safe to merge
							if ( ! $prevent_conflict->ID ) {
								update_user_meta( $user->ID, 'shibboleth_account', true );
								if ( in_array( 'account_merge', $shib_logging ) || defined( 'WP_DEBUG' ) && WP_DEBUG ) {
										error_log( '[Shibboleth WordPress Plugin Logging] SUCCESS: User ' . $user->user_login . ' (ID: ' . $user->ID . ') merged accounts manually using username bypass. Username provided by attribute is: ' . $username . '.' );
									}
								wp_safe_redirect( get_edit_user_link() . '?shibboleth=linked' );
								exit;
							// If there is an existing account with the email, fail
							} else {
								if ( in_array( 'account_merge', $shib_logging ) || defined( 'WP_DEBUG' ) && WP_DEBUG ) {
									error_log( '[Shibboleth WordPress Plugin Logging] ERROR: User ' . $user->user_login . ' (ID: ' . $user->ID . ') failed to manually merge accounts using username bypass. Reason: An account already exists with the email: ' . $email . ' .' );
								}
								wp_safe_redirect( get_edit_user_link() . '?shibboleth=failed' );
								exit;
							}
						// If no other conditions are met, fail
						} else {
							if ( in_array( 'account_merge', $shib_logging ) || defined( 'WP_DEBUG' ) && WP_DEBUG ) {
								error_log( '[Shibboleth WordPress Plugin Logging] ERROR: User ' . $user->user_login . ' (ID: ' . $user->ID . ') failed to manually merge accounts. Reason: Username and email do not match what is provided by attributes. Username provided by attribute is: ' . $username . ' and email provided by attribute is ' . $email . '.' );
							}
							wp_safe_redirect( get_edit_user_link() . '?shibboleth=failed' );
							exit;
						}
					// If there is no existing shibboleth session, kick to the shibboleth_session_initiator_url
					// and redirect to this page with the ?shibboleth=link action
					} else {
						$initiator_url = shibboleth_session_initiator_url( get_edit_user_link() . '?shibboleth=link' );
						wp_redirect( $initiator_url );
						exit;
					}
				// If manual merging is disabled, fail
				} else {
					if ( in_array( 'account_merge', $shib_logging ) || defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( '[Shibboleth WordPress Plugin Logging] ERROR: User ' . $user->user_login . ' (ID: ' . $user->ID . ') failed to manually merge accounts. Reason: Manual account merging is disabled.' );
					}
					wp_safe_redirect( get_edit_user_link() . '?shibboleth=failed' );
					exit;
				}
			// If account is already merged, warn
			} else {
				if ( in_array( 'account_merge', $shib_logging ) || defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( '[Shibboleth WordPress Plugin Logging] WARN: User ' . $user->user_login . ' (ID: ' . $user->ID . ') failed to manually merge accounts. Reason: User\'s account is already merged.' );
				}
				wp_safe_redirect( get_edit_user_link() . '?shibboleth=duplicate' );
				exit;
			}
		}
	}
}
add_action( 'current_screen', 'shibboleth_link_accounts' );


/**
 * Prevents local password changes when local authentication is disabled
 *
 * @since 1.9
 */
function shibboleth_disable_password_changes() {
	$disable = shibboleth_getoption( 'shibboleth_disable_local_auth', false );

	$bypass = defined( 'SHIBBOLETH_ALLOW_LOCAL_AUTH' ) && SHIBBOLETH_ALLOW_LOCAL_AUTH;

	if ( $disable && ! $bypass ) {
			add_filter( 'show_password_fields', '__return_false' );
	}
}

add_action( 'current_screen', 'shibboleth_disable_password_changes' );

/**
 * Displays admin notices based off query string.
 *
 * @since 1.9
 */
function shibboleth_link_accounts_notice() {
	if ( isset( $_GET['shibboleth'] ) ) {
		if ( $_GET['shibboleth'] === 'failed' ) {
			$class = 'notice notice-error';
			$message = __( 'Your account was unable to be linked with Shibboleth.', 'shibboleth' );
		} elseif ( $_GET['shibboleth'] === 'linked' ) {
			$class = 'notice notice-success is-dismissible';
			$message = __( 'Your account has been linked with Shibboleth.', 'shibboleth' );
		} elseif ( $_GET['shibboleth'] === 'duplicate' ) {
			$class = 'notice notice-info is-dismissible';
			$message = __( 'Your account is already linked with Shibboleth.', 'shibboleth' );
		} else {
			$class = '';
			$message = '';
		}
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}
}
add_action( 'admin_notices', 'shibboleth_link_accounts_notice' );
