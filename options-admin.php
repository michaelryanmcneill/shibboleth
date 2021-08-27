<?php
/**
 * Shibboleth Options - Admin
 *
 * @todo this file should be cleaned up and organized better
 * @package shibboleth
 */

/**
 * Setup admin tabs for the Shibboleth option page.
 *
 * @param string $current the current tab.
 * @since 1.9
 */
function shibboleth_admin_tabs( $current = 'general' ) {
	$tabs = array(
		'general' => 'General',
		'user' => 'User',
		'authorization' => 'Authorization',
		'logging' => 'Logging',
	);
	echo '<h2 class="nav-tab-wrapper">';
	foreach ( $tabs as $tab => $name ) {
		$class = ( $tab === $current ) ? ' nav-tab-active' : '';
		echo '<a class="nav-tab' . esc_attr( $class ) . '" href="?page=shibboleth-options&tab=' . esc_attr( $tab ) . '">' . esc_html( $name ) . '</a>';
	}
	echo '</h2>';
}

/**
 * Setup admin menus for Shibboleth options.
 *
 * @since ?
 */
function shibboleth_admin_panels() {
	if ( ! is_multisite() ) {
		add_options_page( __( 'Shibboleth Options', 'shibboleth' ), __( 'Shibboleth', 'shibboleth' ), 'manage_options', 'shibboleth-options', 'shibboleth_options_page' );
	}
}
add_action( 'admin_menu', 'shibboleth_admin_panels' );

/**
 * Setup multisite admin menus for Shibboleth options.
 *
 * @since ?
 */
function shibboleth_network_admin_panels() {
	if ( is_multisite() ) {
		add_submenu_page( 'settings.php', __( 'Shibboleth Options', 'shibboleth' ), __( 'Shibboleth', 'shibboleth' ), 'manage_network_options', 'shibboleth-options', 'shibboleth_options_page' );
	}
}
add_action( 'network_admin_menu', 'shibboleth_network_admin_panels' );

/**
 * WordPress options page to configure the Shibboleth plugin.
 *
 * @uses apply_filters() Calls 'shibboleth_plugin_path'
 * @since ?
 */
function shibboleth_options_page() {
	global $wp_roles;
	$message = null;
	$type = null;

	if ( isset( $_POST['submit'] ) ) {
		check_admin_referer( 'shibboleth_update_options' );

		if ( isset( $_GET['tab'] ) ) {
			$tab = $_GET['tab'];
		} else {
			$tab = 'general';
		}

		switch ( $tab ) {
			case 'general':
				if ( ! defined( 'SHIBBOLETH_ATTRIBUTE_ACCESS_METHOD' ) ) {
					update_site_option( 'shibboleth_attribute_access_method', $_POST['attribute_access'] );
				}
				if ( ! defined( 'SHIBBOLETH_ATTRIBUTE_ACCESS_METHOD_FALLBACK' ) ) {
					update_site_option( 'shibboleth_attribute_access_method_fallback', $_POST['attribute_access_fallback'] );
				}
				if ( ! defined( 'SHIBBOLETH_ATTRIBUTE_CUSTOM_ACCESS_METHOD' ) ) {
					update_site_option( 'shibboleth_attribute_custom_access_method', $_POST['attribute_custom_access'] );
				}
				if ( ! defined( 'SHIBBOLETH_LOGIN_URL' ) ) {
					update_site_option( 'shibboleth_login_url', $_POST['login_url'] );
				}
				if ( ! defined( 'SHIBBOLETH_LOGOUT_URL' ) ) {
					update_site_option( 'shibboleth_logout_url', $_POST['logout_url'] );
				}
				if ( ! defined( 'SHIBBOLETH_SPOOF_KEY' ) ) {
					update_site_option( 'shibboleth_spoof_key', $_POST['spoofkey'] );
				}
				if ( ! defined( 'SHIBBOLETH_PASSWORD_CHANGE_URL' ) ) {
					update_site_option( 'shibboleth_password_change_url', $_POST['password_change_url'] );
				}
				if ( ! defined( 'SHIBBOLETH_PASSWORD_RESET_URL' ) ) {
					update_site_option( 'shibboleth_password_reset_url', $_POST['password_reset_url'] );
				}
				if ( ! defined( 'SHIBBOLETH_DEFAULT_TO_SHIB_LOGIN' ) ) {
					update_site_option( 'shibboleth_default_to_shib_login', ! empty( $_POST['default_login'] ) );
				}
				if ( ! defined( 'SHIBBOLETH_AUTO_LOGIN' ) ) {
					update_site_option( 'shibboleth_auto_login', ! empty( $_POST['auto_login'] ) );
				}
				if ( ! defined( 'SHIBBOLETH_BUTTON_TEXT' ) ) {
					update_site_option( 'shibboleth_button_text', $_POST['button_text'] );
				}
				if ( ! defined( 'SHIBBOLETH_DISABLE_LOCAL_AUTH' ) ) {
					update_site_option( 'shibboleth_disable_local_auth', ! empty( $_POST['disable_local_auth'] ) );
				}
				break;
			case 'user':
				if ( ! defined( 'SHIBBOLETH_HEADERS' ) ) {
					$shib_headers = (array) get_site_option( 'shibboleth_headers' );
					$shib_headers = array_merge( $shib_headers, $_POST['headers'] );
					/**
					 * Filter shibboleth_form_submit_headers
					 *
					 * @param $shib_headers array
					 * @since 1.4
					 * Hint: access $_POST within the filter.
					 */
					$shib_headers = apply_filters( 'shibboleth_form_submit_headers', $shib_headers );
					update_site_option( 'shibboleth_headers', $shib_headers );
				}
				if ( ! defined( 'SHIBBOLETH_CREATE_ACCOUNTS' ) ) {
					update_site_option( 'shibboleth_create_accounts', ! empty( $_POST['create_accounts'] ) );
				}
				if ( ! defined( 'SHIBBOLETH_AUTO_COMBINE_ACCOUNTS' ) ) {
					update_site_option( 'shibboleth_auto_combine_accounts', $_POST['auto_combine_accounts'] );
				}
				if ( ! defined( 'SHIBBOLETH_MANUALLY_COMBINE_ACCOUNTS' ) ) {
					update_site_option( 'shibboleth_manually_combine_accounts', $_POST['manually_combine_accounts'] );
				}
				break;
			case 'authorization':
				if ( ! defined( 'SHIBBOLETH_ROLES' ) ) {
					$shib_roles = (array) get_site_option( 'shibboleth_roles' );
					$shib_roles = array_merge( $shib_roles, $_POST['shibboleth_roles'] );
					/**
					 * Filter shibboleth_form_submit_roles
					 *
					 * @param $shib_roles array
					 * @since 1.4
					 * Hint: access $_POST within the filter.
					 */
					$shib_roles = apply_filters( 'shibboleth_form_submit_roles', $shib_roles );
					update_site_option( 'shibboleth_roles', $shib_roles );
				}
				if ( ! defined( 'SHIBBOLETH_DEFAULT_ROLE' ) ) {
					update_site_option( 'shibboleth_default_role', $_POST['default_role'] );
				}
				if ( ! defined( 'SHIBBOLETH_UPDATE_ROLES' ) ) {
					update_site_option( 'shibboleth_update_roles', ! empty( $_POST['update_roles'] ) );
				}
				break;
			case 'logging':
				if ( ! defined( 'SHIBBOLETH_LOGGING' ) ) {
					if ( isset( $_POST['logging'] ) ) {
						update_site_option( 'shibboleth_logging', $_POST['logging'] );
					} else {
						update_site_option( 'shibboleth_logging', array() );
					}
				}
				break;
		}
		$type = 'updated';
		$message = __( 'Settings saved.', 'shibboleth' );

		if ( function_exists( 'add_settings_error' ) ) {
			add_settings_error( 'shibboleth_settings_updated', esc_attr( 'shibboleth_settings_updated' ), $message, $type );
			settings_errors( 'shibboleth_settings_updated' );
		}

		/**
		 * Action shibboleth_form_submit
		 *
		 * @since 1.4
		 * Hint: use global $_POST within the action.
		 */
		do_action( 'shibboleth_form_submit' );

	}

	$shibboleth_plugin_path = apply_filters( 'shibboleth_plugin_path', plugins_url( 'shibboleth' ) );

	?>
	<div class="wrap">
		<form method="post">

			<h1><?php esc_html_e( 'Shibboleth Options', 'shibboleth' ); ?></h1>

			<?php
			if ( isset( $_GET['tab'] ) ) {
				shibboleth_admin_tabs( $_GET['tab'] );
			} else {
				shibboleth_admin_tabs( 'general' );
			}
			if ( isset( $_GET['tab'] ) ) {
				$tab = $_GET['tab'];
			} else {
				$tab = 'general';
			}

			switch ( $tab ) {
				case 'general':
					$constant = false;
					list( $login_url, $from_constant ) = shibboleth_getoption( 'shibboleth_login_url', false, false, true );
					$constant = $constant || $from_constant;
					list( $logout_url, $from_constant ) = shibboleth_getoption( 'shibboleth_logout_url', false, false, true );
					$constant = $constant || $from_constant;
					list( $password_change_url, $from_constant ) = shibboleth_getoption( 'shibboleth_password_change_url', false, false, true );
					$constant = $constant || $from_constant;
					list( $password_reset_url, $from_constant ) = shibboleth_getoption( 'shibboleth_password_reset_url', false, false, true );
					$constant = $constant || $from_constant;
					list( $attribute_access, $from_constant ) = shibboleth_getoption( 'shibboleth_attribute_access_method', false, false, true );
					$constant = $constant || $from_constant;
					list( $attribute_access_fallback, $from_constant ) = shibboleth_getoption( 'shibboleth_attribute_access_method_fallback', false, false, true );
					$constant = $constant || $from_constant;
					list( $attribute_custom_access, $from_constant ) = shibboleth_getoption( 'shibboleth_attribute_custom_access_method', false, false, true );
					$constant = $constant || $from_constant;
					list( $spoofkey, $from_constant ) = shibboleth_getoption( 'shibboleth_spoof_key', false, false, true );
					$constant = $constant || $from_constant;
					list( $default_login, $from_constant ) = shibboleth_getoption( 'shibboleth_default_to_shib_login', false, false, true );
					$constant = $constant || $from_constant;
					list( $auto_login, $from_constant ) = shibboleth_getoption( 'shibboleth_auto_login', false, false, true );
					$constant = $constant || $from_constant;
					list( $disable_local_auth, $from_constant ) = shibboleth_getoption( 'shibboleth_disable_local_auth', false, false, true );
					$constant = $constant || $from_constant;
					list( $button_text, $from_constant ) = shibboleth_getoption( 'shibboleth_button_text', false, false, true );
					$constant = $constant || $from_constant;
					?>

			<h3><?php esc_html_e( 'General Configuration', 'shibboleth' ); ?></h3>
					<?php if ( $constant ) { ?>
				<div class="notice notice-warning">
					<p><?php echo wp_kses_post( __( '<strong>Note:</strong> Some options below are defined in the <code>wp-config.php</code> file as constants and cannot be modified from this page.', 'shibboleth' ) ); ?></p>
				</div>
			<?php } ?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="login_url"><?php esc_html_e( 'Login URL', 'shibboleth' ); ?></label></th>
					<td>
						<input type="text" id="login_url" name="login_url" value="<?php echo esc_url( $login_url ); ?>" size="50" <?php defined( 'SHIBBOLETH_LOGIN_URL' ) && disabled( $login_url, SHIBBOLETH_LOGIN_URL ); ?> /><br />
						<?php
						esc_html_e(
							'This URL is constructed from values found in your main Shibboleth
							 SP configuration file: your site hostname, the Sessions handlerURL,
							 and the SessionInitiator Location.',
							'shibboleth'
						);
						?>
						<br /><?php esc_html_e( 'Wiki Documentation', 'shibboleth' ); ?>:
						<a href="https://spaces.internet2.edu/display/SHIB/SessionInitiator" target="_blank">Shibboleth 1.3</a> |
						<a href="https://spaces.internet2.edu/display/SHIB2/NativeSPSessionInitiator" target="_blank">Shibboleth 2</a>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="logout_url"><?php esc_html_e( 'Logout URL', 'shibboleth' ); ?></label></th>
					<td>
						<input type="text" id="logout_url" name="logout_url" value="<?php echo esc_url( $logout_url ); ?>" size="50" <?php defined( 'SHIBBOLETH_LOGOUT_URL' ) && disabled( $logout_url, SHIBBOLETH_LOGOUT_URL ); ?> /><br />
						<?php
						esc_html_e(
							'This URL is constructed from values found in your main Shibboleth
							 SP configuration file: your site hostname, the Sessions handlerURL,
							 and the LogoutInitiator Location (also known as the
							 SingleLogoutService Location in Shibboleth 1.3).',
							'shibboleth'
						);
						?>
						<br /><?php esc_html_e( 'Wiki Documentation', 'shibboleth' ); ?>:
						<a href="https://spaces.internet2.edu/display/SHIB/SPMainConfig" target="_blank">Shibboleth 1.3</a> |
						<a href="https://spaces.internet2.edu/display/SHIB2/NativeSPLogoutInitiator" target="_blank">Shibboleth 2</a>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="password_change_url"><?php esc_html_e( 'Password Change URL', 'shibboleth' ); ?></label></th>
					<td>
						<input type="text" id="password_change_url" name="password_change_url" value="<?php echo esc_url( $password_change_url ); ?>" size="50" <?php defined( 'SHIBBOLETH_PASSWORD_CHANGE_URL' ) && disabled( $password_change_url, SHIBBOLETH_PASSWORD_CHANGE_URL ); ?> /><br />
						<?php esc_html_e( 'If this option is set, Shibboleth users will see a "change password" link on their profile page directing them to this URL.', 'shibboleth' ); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="password_reset_url"><?php esc_html_e( 'Password Reset URL', 'shibboleth' ); ?></label></th>
					<td>
						<input type="text" id="password_reset_url" name="password_reset_url" value="<?php echo esc_url( $password_reset_url ); ?>" size="50" <?php defined( 'SHIBBOLETH_PASSWORD_RESET_URL' ) && disabled( $password_reset_url, SHIBBOLETH_PASSWORD_RESET_URL ); ?> /><br />
						<?php echo wp_kses_post( __( 'If this option is set, wp-login.php will send <b><i>ALL</i></b> users here to reset their password.', 'shibboleth' ) ); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="attribute_access"><?php esc_html_e( 'Attribute Access', 'shibboleth' ); ?></label></th>
					<td>
						<select id="attribute_access" name="attribute_access" <?php defined( 'SHIBBOLETH_ATTRIBUTE_ACCESS_METHOD' ) && disabled( $attribute_access, SHIBBOLETH_ATTRIBUTE_ACCESS_METHOD ); ?>>
							<option value="standard" <?php selected( $attribute_access, 'standard' ); ?>><?php esc_html_e( 'Environment Variables', 'shibboleth' ); ?></option>
							<option value="redirect" <?php selected( $attribute_access, 'redirect' ); ?>><?php esc_html_e( 'Redirected Environment Variables', 'shibboleth' ); ?></option>
							<option value="http" <?php selected( $attribute_access, 'http' ); ?>><?php esc_html_e( 'HTTP Headers', 'shibboleth' ); ?></option>
							<option value="custom" <?php selected( $attribute_access, 'custom' ); ?>><?php esc_html_e( 'Custom Prefix', 'shibboleth' ); ?></option>
						</select>
						<p>
						<?php
						echo wp_kses_post(
							__(
								'By default, attributes passed from your Shibboleth Service Provider will be accessed using standard environment variables.
								For most users, leaving these defaults is perfectly fine. If you are running a special server configuration that results in environment variables
								being sent with the prefix <code>REDIRECT_</code>, you should select the "Redirected Environment Variables" option. If you are running
								your Shibboleth Service Provider on a reverse proxy, you should select the "HTTP Headers" option and, if at all possible, add a spoofkey below.
								 If you are running Shibboleth with a custom prefix, you should select the "Custom Prefix" option and complete the "Custom Attribute Access Prefix" field that appears below.',
								'shibboleth'
							)
						);
						?>
						</p>
					</td>
				</tr>
				<tr id="attribute_custom_access_row" <?php echo ( 'custom' === $attribute_access ? '' : 'style="display:none;"' ); ?>>
					<th scope="row"><label for="attribute_custom_access"><?php esc_html_e( 'Custom Attribute Access Prefix', 'shibboleth' ); ?></label></th>
					<td>
						<input type="text" id="attribute_custom_access" name="attribute_custom_access" value="<?php echo esc_attr( $attribute_custom_access ); ?>" size="50" <?php defined( 'SHIBBOLETH_ATTRIBUTE_CUSTOM_ACCESS_METHOD' ) && disabled( $attribute_custom_access, SHIBBOLETH_ATTRIBUTE_CUSTOM_ACCESS_METHOD ); ?> /><br />
						<p>
						<?php
						echo wp_kses_post(
							__(
								'If you wish to use a custom attribute access prefix, enter it here. This field is case-insensitive.
								<br /><b>WARNING:</b> If you incorrectly set this option, you will force <b><i>ALL</i></b> attempts to authenticate with Shibboleth to fail.',
								'shibboleth'
							)
						);
						?>
						</p>
					</td>
				</tr>
				<tr id="spoofkey_row" <?php echo ( 'http' === $attribute_access ? '' : 'style="display:none;"' ); ?>>
					<th scope="row"><label for="spoofkey"><?php esc_html_e( 'Spoof Key', 'shibboleth' ); ?></label></th>
					<td>
						<input type="text" id="spoofkey" name="spoofkey" value="<?php echo esc_attr( $spoofkey ); ?>" size="50" <?php defined( 'SHIBBOLETH_SPOOF_KEY' ) && disabled( $spoofkey, SHIBBOLETH_SPOOF_KEY ); ?> /><br />
						<p>
						<?php
						echo wp_kses_post(
							__(
								'For more details on setting a spoof key on the Shibboleth Service Provider, see <a href="https://wiki.shibboleth.net/confluence/display/SHIB2/NativeSPSpoofChecking">this wiki document</a>.
								<br /><b>WARNING:</b> If you incorrectly set this option, you will force <b><i>ALL</i></b> attempts to authenticate with Shibboleth to fail.',
								'shibboleth'
							)
						);
						?>
						</p>
					</td>
				</tr>
				<tr id="attribute_access_fallback_row" <?php echo 'standard' === $attribute_access ? 'style="display:none;"' : ''; ?>>
					<th scope="row"><label for="attribute_access_fallback"><?php esc_html_e( 'Enable Fallback Attribute Access', 'shibboleth' ); ?></label></th>
					<td>
						<input type="checkbox" id="attribute_access_fallback" name="attribute_access_fallback" <?php checked( (bool) $attribute_access_fallback ); ?> <?php defined( 'SHIBBOLETH_ATTRIBUTE_ACCESS_METHOD_FALLBACK' ) && disabled( $attribute_access_fallback, SHIBBOLETH_ATTRIBUTE_ACCESS_METHOD_FALLBACK ); ?> />
						<label for="attribute_access_fallback"><?php esc_html_e( 'Allow the standard environment variables to be used as a fallback for attribute access.', 'shibboleth' ); ?></label>

						<p>
						<?php
						esc_html_e(
							'If set, this will fallback to standard environment variables when the selected
							 attribute access method fails.',
							'shibboleth'
						);
						?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="default_login"><?php esc_html_e( 'Default Login Method', 'shibboleth' ); ?></label></th>
					<td>
						<input type="checkbox" id="default_login" name="default_login" <?php checked( (bool) $default_login ); ?> <?php defined( 'SHIBBOLETH_DEFAULT_TO_SHIB_LOGIN' ) && disabled( $default_login, SHIBBOLETH_DEFAULT_TO_SHIB_LOGIN ); ?> />
						<label for="default_login"><?php esc_html_e( 'Use Shibboleth as the default login method for users.', 'shibboleth' ); ?></label>

						<p>
						<?php
						esc_html_e(
							'If set, this will cause all standard WordPress login links to initiate Shibboleth
							 login instead of local WordPress authentication.  Shibboleth login can always be
							 initiated from the WordPress login form by clicking the "Log in with Shibboleth" link.',
							'shibboleth'
						);
						?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="auto_login"><?php esc_html_e( 'Automatic Login', 'shibboleth' ); ?></label></th>
					<td>
						<input type="checkbox" id="auto_login" name="auto_login" <?php checked( (bool) $auto_login ); ?> <?php defined( 'SHIBBOLETH_AUTO_LOGIN' ) && disabled( $auto_login, SHIBBOLETH_AUTO_LOGIN ); ?> />
						<label for="auto_login"><?php esc_html_e( 'Use Shibboleth to auto-login users.', 'shibboleth' ); ?></label>

						<p>
						<?php
						echo wp_kses_post(
							__(
								'If set, this option checks to see if a Shibboleth session exists on every page load, and,
								if it does, forces a <code>wp_signon()</code> call and <code>wp_safe_redirect()</code> back to the <code>$_SERVER[\'REQUEST_URI\']</code>.',
								'shibboleth'
							)
						);
						?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="disable_local_auth"><?php esc_html_e( 'Disable Local Authentication', 'shibboleth' ); ?></label></th>
					<td>
						<input type="checkbox" id="disable_local_auth" name="disable_local_auth" <?php checked( (bool) $disable_local_auth ); ?> <?php defined( 'SHIBBOLETH_DISABLE_LOCAL_AUTH' ) && disabled( $disable_local_auth, SHIBBOLETH_DISABLE_LOCAL_AUTH ); ?> />
						<label for="disable_local_auth"><?php esc_html_e( 'Disables local WordPress authentication.', 'shibboleth' ); ?></label>
						<p>
						<?php
						echo wp_kses_post(
							__(
								'<b>WARNING:</b> Disabling local authentication can potentially lock you out of WordPress if you have misconfigured the plugin or have a non-functional Shibboleth Service Provider.
								Make sure that you are confident your configuration is functional before enabling this option.',
								'shibboleth'
							)
						);
						?>
						</p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="button_text"><?php esc_html_e( 'Button Text', 'shibboleth' ); ?></label></th>
					<td>
						<input type="text" id="button_text" name="button_text" value="<?php echo esc_attr( $button_text ); ?>" size="50" <?php defined( 'SHIBBOLETH_BUTTON_TEXT' ) && disabled( $button_text, SHIBBOLETH_BUTTON_TEXT ); ?> /><br />
						<p><?php echo wp_kses_post( __( 'Set the text of the button that appears on the <code>wp-login.php</code> page.', 'shibboleth' ) ); ?></p>
					</td>
				</tr>
					<?php
					/**
					 * Action shibboleth_options_table
					 * Add your own Shibboleth options items to the Shibboleth options table.
					 * Note: This is in a <table> so add a <tr> with appropriate styling.
					 *
					 * @param $shib_headers array
					 * @param $shib_roles array
					 * @since 1.4
					 * @todo support new structure of table and tabs
					 */
					// do_action( 'shibboleth_options_table', $shib_headers, $shib_roles );
					?>
			</table>

			<br class="clear" />

			<script type="text/javascript">
				var attribute_access = document.getElementById("attribute_access");
				attribute_access.onchange=AttributeAccessMethod;
				function AttributeAccessMethod() {
					var attribute_access = document.getElementById("attribute_access");
					var selectedValue = attribute_access.options[attribute_access.selectedIndex].value;

					if (selectedValue === "custom") {
						document.getElementById("attribute_custom_access_row").style.display = "table-row";
						document.getElementById("attribute_access_fallback_row").style.display = "table-row";
						document.getElementById("spoofkey_row").style.display = "none";
					} else if (selectedValue === "http") {
						document.getElementById("attribute_custom_access_row").style.display = "none";
						document.getElementById("attribute_access_fallback_row").style.display = "table-row";
						document.getElementById("spoofkey_row").style.display = "table-row";
					} else if (selectedValue === "standard") {
						document.getElementById("attribute_custom_access_row").style.display = "none";
						document.getElementById("attribute_access_fallback_row").style.display = "none";
						document.getElementById("spoofkey_row").style.display = "none";
					} else {
						document.getElementById("attribute_custom_access_row").style.display = "none";
						document.getElementById("attribute_access_fallback_row").style.display = "table-row";
						document.getElementById("spoofkey_row").style.display = "none";
					}
				}
			</script>

					<?php
					break;
				case 'user':
					$constant = false;
					list( $shib_headers, $shib_headers_constant ) = shibboleth_getoption( 'shibboleth_headers', array(), true, true );
					$constant = $constant || $shib_headers_constant;
					list( $create_accounts, $from_constant ) = shibboleth_getoption( 'shibboleth_create_accounts', false, false, true );
					$constant = $constant || $from_constant;
					list( $auto_combine_accounts, $from_constant ) = shibboleth_getoption( 'shibboleth_auto_combine_accounts', false, false, true );
					$constant = $constant || $from_constant;
					list( $manually_combine_accounts, $from_constant ) = shibboleth_getoption( 'shibboleth_manually_combine_accounts', false, false, true );
					$constant = $constant || $from_constant;
					?>


			<h2><?php esc_html_e( 'User Configuration', 'shibboleth' ); ?></h2>
					<?php if ( $constant ) { ?>
				<div class="notice notice-warning">
					<p><?php echo wp_kses_post( __( '<strong>Note:</strong> Some options below are defined in the <code>wp-config.php</code> file as constants and cannot be modified from this page.', 'shibboleth' ) ); ?></p>
				</div>
			<?php } ?>
			<h4><?php esc_html_e( 'User Profile Data', 'shibboleth' ); ?></h4>

			<p>
					<?php
					echo wp_kses_post(
						__(
							'Define the Shibboleth headers which should be mapped to each user profile attribute.  These
							 header names are configured in <code>attribute-map.xml</code> (for Shibboleth 2.x) or
							 <code>AAP.xml</code> (for Shibboleth 1.x).',
							'shibboleth'
						)
					);
					?>
			</p>

			<p>
					<?php esc_html_e( 'Wiki Documentation', 'shibboleth' ); ?>:
				<a href="https://spaces.internet2.edu/display/SHIB/AttributeAcceptancePolicy" target="_blank">Shibboleth 1.3</a> |
				<a href="https://spaces.internet2.edu/display/SHIB2/NativeSPAddAttribute" target="_blank">Shibboleth 2</a>
			</p>

			<table class="form-table optiontable editform" cellspacing="2" cellpadding="5">
				<tr valign="top">
					<th scope="row"><label for="username"><?php esc_html_e( 'Username' ); ?></label></th>
					<td>
						<input type="text" id="username" name="headers[username][name]" value="<?php echo esc_attr( $shib_headers['username']['name'] ); ?>" <?php disabled( $shib_headers_constant ); ?>/>
					</td>
					<td width="60%">
						<input type="checkbox" id="username_managed" name="headers[username][managed]" <?php checked( true ); ?><?php disabled( true ); ?>/> <?php esc_html_e( 'Managed', 'shibboleth' ); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="first_name"><?php esc_html_e( 'First name' ); ?></label></th>
					<td>
						<input type="text" id="first_name" name="headers[first_name][name]" value="<?php echo esc_attr( $shib_headers['first_name']['name'] ); ?>" <?php disabled( $shib_headers_constant ); ?>/>
					</td>
					<td>
						<input type="checkbox" id="first_name_managed" name="headers[first_name][managed]" <?php isset( $shib_headers['first_name']['managed'] ) && checked( $shib_headers['first_name']['managed'], 'on' ); ?><?php disabled( $shib_headers_constant ); ?> /> <?php esc_html_e( 'Managed', 'shibboleth' ); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="last_name"><?php esc_html_e( 'Last name' ); ?></label></th>
					<td>
						<input type="text" id="last_name" name="headers[last_name][name]" value="<?php echo esc_attr( $shib_headers['last_name']['name'] ); ?>" <?php disabled( $shib_headers_constant ); ?>/>
					</td>
					<td>
						<input type="checkbox" id="last_name_managed" name="headers[last_name][managed]" <?php isset( $shib_headers['last_name']['managed'] ) && checked( $shib_headers['last_name']['managed'], 'on' ); ?><?php disabled( $shib_headers_constant ); ?> /> <?php esc_html_e( 'Managed', 'shibboleth' ); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="nickname"><?php esc_html_e( 'Nickname' ); ?></label></th>
					<td>
						<input type="text" id="nickname" name="headers[nickname][name]" value="<?php echo esc_attr( $shib_headers['nickname']['name'] ); ?>" <?php disabled( $shib_headers_constant ); ?>/>
					</td>
					<td>
						<input type="checkbox" id="nickname_managed" name="headers[nickname][managed]" <?php isset( $shib_headers['nickname']['managed'] ) && checked( $shib_headers['nickname']['managed'], 'on' ); ?><?php disabled( $shib_headers_constant ); ?>/> <?php esc_html_e( 'Managed', 'shibboleth' ); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="_display_name"><?php esc_html_e( 'Display name', 'shibboleth' ); ?></label></th>
					<td>
						<input type="text" id="_display_name" name="headers[display_name][name]" value="<?php echo esc_attr( $shib_headers['display_name']['name'] ); ?>" <?php disabled( $shib_headers_constant ); ?>/>
					</td>
					<td>
						<input type="checkbox" id="display_name_managed" name="headers[display_name][managed]" <?php isset( $shib_headers['display_name']['managed'] ) && checked( $shib_headers['display_name']['managed'], 'on' ); ?><?php disabled( $shib_headers_constant ); ?>/> <?php esc_html_e( 'Managed', 'shibboleth' ); ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="email"><?php esc_html_e( 'Email Address', 'shibboleth' ); ?></label></th>
					<td>
						<input type="text" id="email" name="headers[email][name]" value="<?php echo esc_attr( $shib_headers['email']['name'] ); ?>" <?php disabled( $shib_headers_constant ); ?>/>
					</td>
					<td>
						<input type="checkbox" id="email_managed" name="headers[email][managed]" <?php isset( $shib_headers['email']['managed'] ) && checked( $shib_headers['email']['managed'], 'on' ); ?><?php disabled( $shib_headers_constant ); ?> /> <?php esc_html_e( 'Managed', 'shibboleth' ); ?>
					</td>
				</tr>
			</table>

			<p>
					<?php
					echo wp_kses_post(
						__(
							'<em>Managed</em> profile fields are updated each time the user logs in using the current
							 data provided by Shibboleth.  Additionally, users will be prevented from manually updating these
							 fields from within WordPress.  Note that Shibboleth data is always used to populate the user
							 profile during initial account creation.',
							'shibboleth'
						)
					);
					?>
			</p>

			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="create_accounts"><?php esc_html_e( 'Automatically Create Accounts', 'shibboleth' ); ?></label></th>
					<td>
						<input type="checkbox" id="create_accounts" name="create_accounts" <?php checked( (bool) $create_accounts ); ?> <?php defined( 'SHIBBOLETH_CREATE_ACCOUNTS' ) && disabled( $create_accounts, SHIBBOLETH_CREATE_ACCOUNTS ); ?> />
						<label for="create_accounts"><?php esc_html_e( 'Automatically create new users if they do not exist in the WordPress database.', 'shibboleth' ); ?></label>
						<p>
						<?php
						echo wp_kses_post(
							__(
								'Automatically created users will be provisioned with the role that they map to, as defined on the <a href="?page=shibboleth-options&tab=authorization">Authorization</a> tab.
								If a user does not match any mappings, they will be placed into the role selected under "Default Role" on the <a href="?page=shibboleth-options&tab=authorization">Authorization</a> tab.',
								'shibboleth'
							)
						);
						?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="auto_combine_accounts"><?php esc_html_e( 'Combine Local and Shibboleth Accounts', 'shibboleth' ); ?></label></th>
					<td>
						<select id="auto_combine_accounts" name="auto_combine_accounts" <?php defined( 'SHIBBOLETH_AUTO_COMBINE_ACCOUNTS' ) && disabled( $auto_combine_accounts, SHIBBOLETH_AUTO_COMBINE_ACCOUNTS ); ?>>
							<option value="prevent" <?php selected( $auto_combine_accounts, 'disallow' ); ?>>Prevent Automatic Account Merging</option>
							<option value="allow" <?php selected( $auto_combine_accounts, 'allow' ); ?>>Allow Automatic Account Merging</option>
							<option value="bypass" <?php selected( $auto_combine_accounts, 'bypass' ); ?>>Allow Automatic Account Merging (Bypass Username Management)</option>
						</select>
						<p>
						<?php
						echo wp_kses_post(
							__(
								'By default, users will receive an error if they log in via Shibboleth and have a pre-existing local WordPress user account that has not previously been linked with Shibboleth. <br /><br />
								<code>Prevent Automatic Account Merging</code>: This option prevents automatic merging of accounts.<br />
								<code>Allow Automatic Account Merging</code>: This option prevents users from experiencing an error if they share a username with both a local and a Shibboleth account.
								This option <b>WILL NOT</b> prevent an error if another user shares the email passed via Shibboleth attributes.<br />
								<code>Allow Automatic Account Merging (Bypass Username Management)</code>: Occasionally, users have pre-existing local WordPress user accounts with a different username than that provided via Shibboleth attributes.
								This option prevents users from experiencing an error in this case by bypassing the username management requirement.',
								'shibboleth'
							)
						);
						?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="manually_combine_accounts"></label></th>
					<td>
						<select id="manually_combine_accounts" name="manually_combine_accounts" <?php defined( 'SHIBBOLETH_MANUALLY_COMBINE_ACCOUNTS' ) && disabled( $manually_combine_accounts, SHIBBOLETH_MANUALLY_COMBINE_ACCOUNTS ); ?>>
							<option value="prevent" <?php selected( $manually_combine_accounts, 'disallow' ); ?>>Prevent Manual Account Merging</option>
							<option value="allow" <?php selected( $manually_combine_accounts, 'allow' ); ?>>Allow Manual Account Merging</option>
							<option value="bypass" <?php selected( $manually_combine_accounts, 'bypass' ); ?>>Allow Manual Account Merging (Bypass Username Management)</option>
						</select>
						<p>
						<?php
						echo wp_kses_post(
							__(
								'This option offers users the ability to manually link their local accounts to Shibboleth from their profile page.<br /><br />
								<code>Prevent Manual Account Merging</code>: This option does not allow users to manually link accounts.<br />
								<code>Allow Manual Account Merging</code>: This option allows users to manually link accounts if they share a username with both a local and a Shibboleth account.
								This option <b>WILL NOT</b> prevent an error if another user shares the email passed via Shibboleth attributes.<br />
								<code>Allow Manual Account Merging (Bypass Username Management)</code>: Occasionally, users have pre-existing local WordPress user accounts with a different username than that provided via Shibboleth attributes.
								This option allows users to manually link accounts by bypassing the username management requirement.',
								'shibboleth'
							)
						);
						?>
						</p>
					</td>
				</tr>
			</table>

					<?php
					break;
				case 'authorization':
					$constant = false;
					list( $shib_roles, $shib_roles_constant ) = shibboleth_getoption( 'shibboleth_roles', array(), true, true );
					$constant = $constant || $shib_roles_constant;
					list( $default_role, $from_constant ) = shibboleth_getoption( 'shibboleth_default_role', false, false, true );
					$constant = $constant || $from_constant;
					list( $update_roles, $from_constant ) = shibboleth_getoption( 'shibboleth_update_roles', false, false, true );
					$constant = $constant || $from_constant;
					?>

			<h3><?php esc_html_e( 'User Role Mappings', 'shibboleth' ); ?></h3>
						<?php if ( $constant ) { ?>
				<div class="notice notice-warning">
					<p><?php echo wp_kses_post( __( '<strong>Note:</strong> Some options below are defined in the <code>wp-config.php</code> file as constants and cannot be modified from this page.', 'shibboleth' ) ); ?></p>
				</div>
							<?php
						}

						/**
						 * Filter shibboleth_role_mapping_override
						 * Return true to override the default user role mapping form
						 *
						 * @param boolean - default value false
						 * @return boolean - true if override
						 * @since 1.4
						 *
						 * Use in conjunction with shibboleth_role_mapping_form action below
						 */
						if ( apply_filters( 'shibboleth_role_mapping_override', false ) === false ) {
							?>

				<p>
							<?php
							esc_html_e(
								'Users can be placed into one of WordPress\'s internal roles based on any
								 attribute.  For example, you could define a special eduPersonEntitlement value
								 that designates the user as a WordPress Administrator.  Or you could automatically
								 place all users with an eduPersonAffiliation of "faculty" in the Author role.',
								'shibboleth'
							);
							?>
				</p>

				<p>
							<?php
							echo wp_kses_post(
								__(
									'<strong>Current Limitations:</strong> While WordPress supports users having
									 multiple roles, the Shibboleth plugin will only place the user in the highest ranking
									 role.  Only a single header/value pair is supported for each user role.  This may be
									 expanded in the future to support multiple header/value pairs or regular expression
									 values.  In the meantime, you can use the <em>shibboleth_roles</em> and
									 <em>shibboleth_user_role</em> WordPress filters to provide your own logic for assigning
									 user roles.',
									'shibboleth'
								)
							);
							?>
				</p>

			<style type="text/css">
				#role_mappings { padding: 0; }
				#role_mappings thead th { padding: 5px 10px; }
				#role_mappings td, #role_mappings th { border-bottom: 0px; }
			</style>

			<table class="form-table optiontable editform" cellspacing="2" cellpadding="5" width="100%">
				<tr>
					<th scope="row"><?php esc_html_e( 'Role Mappings', 'shibboleth' ); ?></th>
					<td id="role_mappings">
						<table id="">
						<col width="10%"></col>
						<col></col>
						<col></col>
						<thead>
							<tr>
								<th></th>
								<th scope="column"><?php esc_html_e( 'Header Name', 'shibboleth' ); ?></th>
								<th scope="column"><?php esc_html_e( 'Header Value', 'shibboleth' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php

							foreach ( $wp_roles->role_names as $key => $name ) {
								$header = '';
								if ( isset( $shib_roles[ $key ]['header'] ) ) {
									$header = $shib_roles[ $key ]['header'];
								}
								$value = '';
								if ( isset( $shib_roles[ $key ]['value'] ) ) {
									$value = $shib_roles[ $key ]['value'];
								}
								echo '
						<tr valign="top">
							<th scope="row">' . esc_html( $name ) . '</th>
							<td><input type="text" id="role_' . esc_attr( $key ) . '_header" name="shibboleth_roles[' . esc_attr( $key ) . '][header]" value="' . esc_attr( $header ) . '" style="width: 100%" ' . disabled( $shib_roles_constant, true, false ) . '/></td>
							<td><input type="text" id="role_' . esc_attr( $key ) . '_value" name="shibboleth_roles[' . esc_attr( $key ) . '][value]" value="' . esc_attr( $value ) . '" style="width: 100%" ' . disabled( $shib_roles_constant, true, false ) . '/></td>
						</tr>';
							}
							?>

						</tbody>
						</table>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Default Role', 'shibboleth' ); ?></th>
					<td>
						<select id="default_role" name="default_role" <?php defined( 'SHIBBOLETH_DEFAULT_ROLE' ) && disabled( $default_role, SHIBBOLETH_DEFAULT_ROLE ); ?>>
							<option value=""><?php esc_html_e( '(no role)', 'shibboleth' ); ?></option>
							<option value="_no_account" <?php selected( $default_role, '_no_account' ); ?>><?php esc_html_e( '(skip \'no role\' account creation)', 'shibboleth' ); ?></option>
							<?php
							foreach ( $wp_roles->role_names as $key => $name ) {
								echo '<option value="' . esc_attr( $key ) . '"' . selected( $default_role, $key ) . '>' . esc_html( $name ) . '</option>';
							}
							?>
						</select>

						<p>
							<?php
							esc_html_e(
								'If a user does not map into any of the roles above, they will
								 be placed into the default role.  If there is no default role, the
								 user will not be assigned a role when creating an account with
								 Shibboleth.  If "(skip \'no role\' account creation)" is selected, the user
								 will not be able to create an account with Shibboleth.',
								'shibboleth'
							);
							?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="update_roles"><?php esc_html_e( 'Update User Roles', 'shibboleth' ); ?></label></th>
					<td>
						<input type="checkbox" id="update_roles" name="update_roles" <?php checked( (bool) $update_roles ); ?> <?php defined( 'SHIBBOLETH_UPDATE_ROLES' ) && disabled( $update_roles, SHIBBOLETH_UPDATE_ROLES ); ?>
							/>
						<label for="update_roles"><?php esc_html_e( 'Use Shibboleth data to update user role mappings each time the user logs in.', 'shibboleth' ); ?></label>

						<p>
							<?php
							echo wp_kses_post(
								__(
									'Be aware that if you use this option, you should <strong>not</strong> update user roles manually,
									 since they will be overwritten from Shibboleth the next time the user logs in.  Note that Shibboleth data
									 is always used to populate the initial user role during account creation.',
									'shibboleth'
								)
							);
							?>
						</p>
					</td>
				</tr>
			</table>

							<?php
						} else {
							/**
							 * Action shibboleth_role_mapping_form
							 * Roll your own custom Shibboleth role mapping admin UI
							 *
							 * @param $shib_headers array
							 * @param $shib_roles array
							 * @since 1.4
							 *
							 * Use in conjunction with shibboleth_role_mapping_override filter
							 */
							do_action( 'shibboleth_role_mapping_form', $shib_headers, $shib_roles );
						} // if ( form override )
					break;
				case 'logging':
					$constant = false;
					list( $shib_logging, $shib_logging_constant ) = shibboleth_getoption( 'shibboleth_logging', array(), true, true );
					$constant = $constant || $shib_logging_constant;
					?>
		<h3><?php esc_html_e( 'Logging Configuration', 'shibboleth' ); ?></h3>
					<?php if ( $constant ) { ?>
			<div class="notice notice-warning">
				<p><?php echo wp_kses_post( __( '<strong>Note:</strong> Some options below are defined in the <code>wp-config.php</code> file as constants and cannot be modified from this page.', 'shibboleth' ) ); ?></p>
			</div>
		<?php } ?>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="log_auth"><?php esc_html_e( 'Log Authentication Attempts', 'shibboleth' ); ?></label></th>
					<td>
						<input type="checkbox" id="log_auth" name="logging[]" value="auth" <?php checked( in_array( 'auth', $shib_logging, true ) ); ?> <?php defined( $shib_logging_constant ) && disabled( $shib_logging_constant, true, false ); ?> />
						<label for="log_auth"><?php esc_html_e( 'Log when a user attempts to authenticate using Shibboleth.', 'shibboleth' ); ?></label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="log_account_merge"><?php esc_html_e( 'Log Account Merges', 'shibboleth' ); ?></label></th>
					<td>
						<input type="checkbox" id="log_account_merge" name="logging[]" value="account_merge" <?php checked( in_array( 'account_merge', $shib_logging, true ) ); ?> <?php defined( $shib_logging_constant ) && disabled( $shib_logging_constant, true, false ); ?> />
						<label for="log_account_merge"><?php esc_html_e( 'Log when a user attempts to merge their account, either manually or automatically.', 'shibboleth' ); ?></label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="log_account_create"><?php esc_html_e( 'Log Account Creation', 'shibboleth' ); ?></label></th>
					<td>
						<input type="checkbox" id="log_account_create" name="logging[]" value="account_create" <?php checked( in_array( 'account_create', $shib_logging, true ) ); ?> <?php defined( $shib_logging_constant ) && disabled( $shib_logging_constant, true, false ); ?> />
						<label for="log_account_create"><?php esc_html_e( 'Log when new accounts are created.', 'shibboleth' ); ?></label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="log_role_update"><?php esc_html_e( 'Log Role Update', 'shibboleth' ); ?></label></th>
					<td>
						<input type="checkbox" id="log_role_update" name="logging[]" value="role_update" <?php checked( in_array( 'role_update', $shib_logging, true ) ); ?> <?php defined( $shib_logging_constant ) && disabled( $shib_logging_constant, true, false ); ?> />
						<label for="log_role_update"><?php esc_html_e( 'Log when the plugin updates a user\'s role.', 'shibboleth' ); ?></label>
					</td>
				</tr>
			</table>
					<?php
					break;
			}

			wp_nonce_field( 'shibboleth_update_options' );
			?>
			<p class="submit">
				<input type="submit" name="submit" class="button-primary" value="<?php esc_html_e( 'Save Changes' ); ?>" />
			</p>
		</form>
	</div>

	<?php
}
