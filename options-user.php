<?php
/**
 * Shibboleth Options - User
 *
 * @package shibboleth
 */

/**
 * For WordPress accounts that were created by Shibboleth, limit what administrators and users
 * can edit via user-edit.php and profile.php.
 *
 * @since 2.3
 */
function shibboleth_edit_user_options() {
	if ( IS_PROFILE_PAGE ) {
		$user_id = wp_get_current_user()->ID;
	} else {
		global $user_id;
	}

	$user_idp = shibboleth_get_user_idp( $user_id );
	if ( ! empty( $user_idp ) ) {
		add_filter( 'show_password_fields', '__return_false' );

		add_action( 'admin_footer-user-edit.php', 'shibboleth_disable_managed_fields' );

		add_action( 'admin_footer-profile.php', 'shibboleth_disable_managed_fields' );
	}
}
add_action( 'personal_options', 'shibboleth_edit_user_options' );

/**
 * For WordPress accounts that were created by Shibboleth, disable certain fields
 * that users/administrators aren't allowed to modify.
 *
 * @since 1.3 (renamed in 2.3 from `shibboleth_admin_footer_profile`)
 */
function shibboleth_disable_managed_fields() {
	$managed_fields = shibboleth_get_managed_user_fields();

	if ( shibboleth_getoption( 'shibboleth_update_roles' ) ) {
		$managed_fields = array_merge( $managed_fields, array( 'role' ) );
	}
	if ( ! empty( $managed_fields ) ) {
		$selectors = join(
			',',
			array_map(
				function ( $a ) {
					return "#$a";
				},
				$managed_fields
			)
		);

		echo "
		<script type=\"text/javascript\">
			jQuery(function () {
				jQuery('" . esc_attr( $selectors ) . "').attr('readonly', true);
				jQuery('#first_name')
					.parents('.form-table')
					.before(
						'<div class=\"updated fade\"><p>"
						. esc_attr( __( 'Some profile fields cannot be changed from WordPress.', 'shibboleth' ) )
						. "</p></div>'
					);
				jQuery('form#your-profile').submit(function () {
					jQuery('" . esc_attr( $selectors ) . "').attr('readonly', false);
				});
				if (jQuery('#email').is(':readonly')) {
					jQuery('#email-description').hide();
				}
			});
		</script>";
	}
}


/**
 * Add change password link to the user profile for Shibboleth users.
 *
 * @since 1.3 (renamed in 2.3 from `shibboleth_show_user_profile`)
 */
function shibboleth_change_password_profile_link() {
	$user = wp_get_current_user();
	$user_idp = shibboleth_get_user_idp( $user->ID );

	if ( $user_idp ) {
		if ( defined( 'SHIBBOLETH_PASSWORD_CHANGE_URL' ) ) {
			$password_change_url = SHIBBOLETH_PASSWORD_CHANGE_URL;
		} else {
			$idps = shibboleth_getoption( 'shibboleth_idps', array() );

			if ( isset( $idps[ $user_idp ] ) ) {
				$password_change_url = $shibboleth_idps[ $user_idp ]['password_change_url'];
			}
		}
	}

	if ( ! empty( $password_change_url ) ) {
		?>
	<table class="form-table">
		<tr>
			<th><?php esc_html_e( 'Change Password', 'shibboleth' ); ?></th>
			<td>
				<a href="<?php echo esc_url( $password_change_url ); ?>" rel="nofollow" target="_blank">
					<?php
					esc_html_e( 'Change your password', 'shibboleth' );
					?>
				</a>
			</td>
		</tr>
	</table>
		<?php
	}
}
add_action( 'show_user_profile', 'shibboleth_change_password_profile_link' );


/**
 * Ensure profile data isn't updated when managed.
 *
 * @since 2.3
 * @param int $user_id The ID of the user.
 */
function shibboleth_prevent_managed_fields_update( $user_id ) {
	$user_idp = shibboleth_get_user_idp( $user_id );

	if ( ! empty( $user_idp ) ) {
		$user = get_user_by( 'id', $user_id );

		$managed = shibboleth_get_managed_user_fields( $user_idp );

		if ( in_array( 'first_name', $managed, true ) ) {
			$_POST['first_name'] = $user->first_name;
		}

		if ( in_array( 'last_name', $managed, true ) ) {
			$_POST['last_name'] = $user->last_name;
		}

		if ( in_array( 'nickname', $managed, true ) ) {
			$_POST['nickname'] = $user->nickname;
		}

		if ( in_array( 'display_name', $managed, true ) ) {
			$_POST['display_name'] = $user->display_name;
		}

		if ( in_array( 'email', $managed, true ) ) {
			$_POST['email'] = $user->user_email;
		}
	}
}
add_action( 'personal_options_update', 'shibboleth_prevent_managed_fields_update' );
add_action( 'edit_user_profile_update', 'shibboleth_prevent_managed_fields_update' );

/**
 * Adds a button to user profile pages if administrator has allowed
 * users to manually combine accounts.
 *
 * @param object $user WP_User object.
 * @since 1.9
 */
function shibboleth_link_accounts_button( $user ) {
	$allowed = shibboleth_getoption( 'shibboleth_manually_combine_accounts', 'disallow' );

	if ( 'allow' === $allowed || 'bypass' === $allowed ) {
		$user_idp = shibboleth_get_user_idp( $user->ID );
		?>
		<table class="form-table">
			<tr>
				<th><label for="link_shibboleth"><?php esc_html_e( 'Link Shibboleth Account', 'shibboleth' ); ?></label></th>
				<td>
					<?php if ( ! empty( $user_idp ) ) { ?>
						<p class="description"><?php esc_html_e( 'Your account is already linked to Shibboleth.', 'shibboleth' ); ?></p>
					<?php } elseif ( defined( 'IS_PROFILE_PAGE' ) && ! IS_PROFILE_PAGE ) { ?>
						<p class="description"><?php esc_html_e( 'This user account has not been linked to Shibboleth.', 'shibboleth' ); ?></p>
					<?php } else { ?>
						<a href="<?php echo esc_url( wp_nonce_url( '?shibboleth=link', 'shibboleth-link' ) ); ?>"><button type="button" class="button"><?php esc_html_e( 'Link Shibboleth Account', 'shibboleth' ); ?></button></a>
						<p class="description"><?php esc_html_e( 'Your account has not been linked to Shibboleth. To link your account, click the button above.', 'shibboleth' ); ?></p>
					<?php } ?>
				</td>
			</tr>
		</table>
		<?php
	}
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

	if ( is_admin() && 'profile' === $screen->id ) {
		$user_id = get_current_user_id();

		// If profile page has ?shibboleth=link action and current user can edit their profile, proceed.
		if ( isset( $_GET['shibboleth'] ) && 'link' === $_GET['shibboleth'] && current_user_can( 'edit_user', $user_id ) ) {
			check_admin_referer( 'shibboleth-link' );

			$allowed = shibboleth_getoption( 'shibboleth_manually_combine_accounts', 'disallow' );

			$user_idp = shibboleth_get_user_idp( $user_id );

			// If user's account is not already linked with shibboleth, proceed.
			if ( empty( $user_idp ) ) {
				// If manual account merging is enabled, proceed.
				if ( 'allow' === $allowed || 'bypass' === $allowed ) {
					// If there is an existing shibboleth session, proceed.
					if ( shibboleth_session_active() ) {
						$shib_headers = shibboleth_getoption( 'shibboleth_headers', false, true );

						$username = shibboleth_getenv( $shib_headers['username']['name'] );
						$email = shibboleth_getenv( $shib_headers['email']['name'] );

						$user = get_user_by( 'id', $user_id );

						$set_user_idp = false;

						if ( $user->user_login === $username && strtolower( $user->user_email ) === strtolower( $email ) ) {
							// If username and email match, safe to merge.
							$set_user_idp = true;
						} elseif ( $user->user_login === $username ) {
							// If username matches, check if there is a conflict with the email.
							$prevent_conflict = get_user_by( 'email', $email );

							// If username matches and there is no existing account with the email, safe to merge.
							if ( ! $prevent_conflict->ID ) {
								$set_user_idp = true;
							} else {
								// If username matches and there is an existing account with the email, fail.
								shibboleth_log_message( 'account_merge', 'ERROR: User ' . $user->user_login . ' (ID: ' . $user->ID . ') failed to manually merge accounts. Reason: An account already exists with the email: ' . $email . ' .' );
							}
						} elseif ( strtolower( $user->user_email ) === strtolower( $email ) && 'bypass' === $allowed ) {
							// If email matches and username bypass is enabled, check if there is a conflict with the username.
							$prevent_conflict = get_user_by( 'user_login', $username );

							// If email matches and there is no existing account with the username, safe to merge.
							if ( ! $prevent_conflict->ID ) {
								$set_user_idp = true;
							} else {
								// If there is an existing account with the email, fail.
								shibboleth_log_message( 'account_merge', 'ERROR: User ' . $user->user_login . ' (ID: ' . $user->ID . ') failed to manually merge accounts using username bypass. Reason: An account already exists with the email: ' . $email . ' .' );
							}
						} else {
							// If no other conditions are met, fail.
							shibboleth_log_message( 'account_merge', 'ERROR: User ' . $user->user_login . ' (ID: ' . $user->ID . ') failed to manually merge accounts. Reason: Username and email do not match what is provided by attributes. Username provided by attribute is: ' . $username . ' and email provided by attribute is ' . $email . '.' );
						}

						if ( $set_user_idp ) {
							if ( shibboleth_set_user_idp( $user->ID ) ) {
								shibboleth_log_message( 'account_merge', 'SUCCESS: User ' . $user->user_login . ' (ID: ' . $user->ID . ') merged accounts manually for IdP: ' . shibboleth_get_user_idp( $user->ID ) . '.' );
								wp_safe_redirect( get_edit_user_link() . '?shibboleth=linked' );
								exit;
							} else {
								shibboleth_log_message( 'account_merge', 'ERROR: User ' . $user->user_login . ' (ID: ' . $user->ID . ') failed to manually merge accounts. Reason: Unable to automatically determine IdP.' );
							}
						}

						wp_safe_redirect( get_edit_user_link() . '?shibboleth=failed' );
						exit;
					} else {
						// If there is no existing shibboleth session, kick to the shibboleth_session_initiator_url
						// and redirect to this page with the ?shibboleth=link action.
						$initiator_url = shibboleth_session_initiator_url( wp_nonce_url( get_edit_user_link() . '?shibboleth=link', 'shibboleth-link' ) );
						wp_redirect( $initiator_url );
						exit;
					}
					// If manual merging is disabled, fail.
				} else {
					shibboleth_log_message( 'account_merge', 'ERROR: User ' . $user->user_login . ' (ID: ' . $user->ID . ') failed to manually merge accounts. Reason: Manual account merging is disabled.' );
					wp_safe_redirect( get_edit_user_link() . '?shibboleth=failed' );
					exit;
				}
				// If account is already merged, warn.
			} else {
				shibboleth_log_message( 'account_merge', 'WARN: User ' . $user->user_login . ' (ID: ' . $user->ID . ') failed to manually merge accounts. Reason: User\'s account is already merged.' );
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
	$message_code = isset( $_GET['shibboleth'] ) ? sanitize_key( wp_unslash( $_GET['shibboleth'] ) ) : '';

	if ( 'failed' === $message_code ) {
		$class = 'notice notice-error';
		$message = __( 'Your account was unable to be linked with Shibboleth.', 'shibboleth' );
	} elseif ( 'linked' === $message_code ) {
		$class = 'notice notice-success is-dismissible';
		$message = __( 'Your account has been linked with Shibboleth.', 'shibboleth' );
	} elseif ( 'duplicate' === $message_code ) {
		$class = 'notice notice-info is-dismissible';
		$message = __( 'Your account is already linked with Shibboleth.', 'shibboleth' );
	}

	if ( isset( $message ) ) {
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}
}
add_action( 'admin_notices', 'shibboleth_link_accounts_notice' );
