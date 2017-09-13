<?php
// functions for managing Shibboleth options through the WordPress administration panel

if ( is_multisite() ) {
	add_action('network_admin_menu', 'shibboleth_network_admin_panels');
} else {
	add_action('admin_menu', 'shibboleth_admin_panels');
}

/**
 * Setup admin menus for Shibboleth options.
 *
 * @action: admin_menu
 **/
function shibboleth_admin_panels() {
	$hookname = add_options_page(__('Shibboleth options', 'shibboleth'),
		__('Shibboleth', 'shibboleth'), 'manage_options', 'shibboleth-options', 'shibboleth_options_page' );

	$screen = WP_Screen::get($hookname);
	$screen->add_help_tab(array(
		'title' => 'Shibboleth Help',
		'id' => 'shibboleth-help',
		'content' => shibboleth_help_text(),
	));
}

/**
 * Setup multisite admin menus for Shibboleth options.
 *
 * @action: network_admin_menu
 **/
function shibboleth_network_admin_panels() {
	$hookname = add_submenu_page('settings.php', __('Shibboleth options', 'shibboleth'),
		__('Shibboleth', 'shibboleth'), 'manage_network_options', 'shibboleth-options', 'shibboleth_options_page' );

	$screen = WP_Screen::get($hookname);
	$screen->add_help_tab(array(
		'title' => 'Shibboleth Help',
		'id' => 'shibboleth-help',
		'content' => shibboleth_help_text(),
	));
}


/**
 * Add Shibboleth links to the "help" pull down panel.
 */
function shibboleth_help_text() {
	$text = '
	<ul>
		<li><a href="https://spaces.internet2.edu/display/SHIB/" target="_blank">' . __('Shibboleth 1.3 Wiki', 'shibboleth') . '</a></li>
		<li><a href="https://spaces.internet2.edu/display/SHIB2/" target="_blank">' . __('Shibboleth 2 Wiki', 'shibboleth') . '</a></li>
		<li><a href="http://shibboleth.internet2.edu/lists.html" target="_blank">' . __('Shibboleth Mailing Lists', 'shibboleth') . '</a></li>
	</ul>';

	return apply_filters( 'shibboleth_help_text_filter', $text );

}


/**
 * WordPress options page to configure the Shibboleth plugin.
 *
 * @uses apply_filters() Calls 'shibboleth_plugin_path'
 */
function shibboleth_options_page() {
	global $wp_roles;
	$message = null;
	$type = null;

	if ( isset($_POST['submit']) ) {
		check_admin_referer('shibboleth_update_options');

		$shib_headers = (array) get_site_option('shibboleth_headers');
		$shib_headers = array_merge($shib_headers, $_POST['headers']);
		/**
		 * filter shibboleth_form_submit_headers
		 * @param $shib_headers array
		 * @since 1.4
		 * Hint: access $_POST within the filter.
		 */
		$shib_headers = apply_filters( 'shibboleth_form_submit_headers', $shib_headers );
		update_site_option('shibboleth_headers', $shib_headers);

		$shib_roles = (array) get_site_option('shibboleth_roles');
		$shib_roles = array_merge($shib_roles, $_POST['shibboleth_roles']);
		/**
		 * filter shibboleth_form_submit_roles
		 * @param $shib_roles array
		 * @since 1.4
		 * Hint: access $_POST within the filter.
		 */
		$shib_roles = apply_filters( 'shibboleth_form_submit_roles', $shib_roles );
		update_site_option('shibboleth_roles', $shib_roles);
		update_site_option('shibboleth_attribute_access', $_POST['attribute_access']);
		update_site_option('shibboleth_login_url', $_POST['login_url']);
		update_site_option('shibboleth_logout_url', $_POST['logout_url']);
		update_site_option('shibboleth_spoofkey', $_POST['spoofkey']);
		update_site_option('shibboleth_password_change_url', $_POST['password_change_url']);
		update_site_option('shibboleth_password_reset_url', $_POST['password_reset_url']);
		update_site_option('shibboleth_default_login', !empty($_POST['default_login']));
		update_site_option('shibboleth_auto_login', !empty($_POST['auto_login']));
		update_site_option('shibboleth_update_users', !empty($_POST['update_users']));
		update_site_option('shibboleth_update_roles', !empty($_POST['update_roles']));
		update_site_option('shibboleth_default_role', $_POST['default_role']);

		$type = 'updated';
		$message = __( 'Settings saved.', 'shibboleth' );

		if ( function_exists( 'add_settings_error' ) ) {
			add_settings_error( 'shibboleth_settings_updated', esc_attr( 'shibboleth_settings_updated' ), $message, $type );
			settings_errors( 'shibboleth_settings_updated' );
		}

		/**
		 * action shibboleth_form_submit
		 * @since 1.4
		 * Hint: use global $_POST within the action.
		 */
		do_action( 'shibboleth_form_submit' );
	}

	$shib_headers = get_site_option('shibboleth_headers');
	$shib_roles = get_site_option('shibboleth_roles');

	$shibboleth_plugin_path = apply_filters('shibboleth_plugin_path', plugins_url('shibboleth'));

	screen_icon('shibboleth');

?>
	<style type="text/css">
		#icon-shibboleth { background: url("<?php echo $shibboleth_plugin_path . '/icon.png' ?>") no-repeat; height: 36px width: 36px; }
	</style>

	<div class="wrap">
		<form method="post">

			<h2><?php _e('Shibboleth Options', 'shibboleth') ?></h2>

			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="login_url"><?php _e('Session Initiator URL', 'shibboleth') ?></label></th>
					<td>
						<input type="text" id="login_url" name="login_url" value="<?php echo get_site_option('shibboleth_login_url') ?>" size="50" /><br />
						<?php _e('This URL is constructed from values found in your main Shibboleth'
							. ' SP configuration file: your site hostname, the Sessions handlerURL,'
							. ' and the SessionInitiator Location.', 'shibboleth'); ?>
						<br /><?php _e('Wiki Documentation', 'shibboleth') ?>:
						<a href="https://spaces.internet2.edu/display/SHIB/SessionInitiator" target="_blank">Shibboleth 1.3</a> |
						<a href="https://spaces.internet2.edu/display/SHIB2/NativeSPSessionInitiator" target="_blank">Shibboleth 2</a>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="logout_url"><?php _e('Logout URL', 'shibboleth') ?></label></th>
					<td>
						<input type="text" id="logout_url" name="logout_url" value="<?php echo get_site_option('shibboleth_logout_url') ?>" size="50" /><br />
						<?php _e('This URL is constructed from values found in your main Shibboleth'
							. ' SP configuration file: your site hostname, the Sessions handlerURL,'
							. ' and the LogoutInitiator Location (also known as the'
							. ' SingleLogoutService Location in Shibboleth 1.3).', 'shibboleth'); ?>
						<br /><?php _e('Wiki Documentation', 'shibboleth') ?>:
						<a href="https://spaces.internet2.edu/display/SHIB/SPMainConfig" target="_blank">Shibboleth 1.3</a> |
						<a href="https://spaces.internet2.edu/display/SHIB2/NativeSPLogoutInitiator" target="_blank">Shibboleth 2</a>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="password_change_url"><?php _e('Password Change URL', 'shibboleth') ?></label></th>
					<td>
						<input type="text" id="password_change_url" name="password_change_url" value="<?php echo get_site_option('shibboleth_password_change_url') ?>" size="50" /><br />
						<?php _e('If this option is set, Shibboleth users will see a "change password" link on their profile page directing them to this URL.', 'shibboleth') ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="password_reset_url"><?php _e('Password Reset URL', 'shibboleth') ?></label></th>
					<td>
						<input type="text" id="password_reset_url" name="password_reset_url" value="<?php echo get_site_option('shibboleth_password_reset_url') ?>" size="50" /><br />
						<?php _e('If this option is set, Shibboleth users who try to reset their forgotten password using WordPress will be redirected to this URL.', 'shibboleth') ?>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="attribute_access"><?php _e('Attribute Access', 'shibboleth') ?></label></th>
					<td>
						<select id="attribute_access" name="attribute_access">
							<option value="standard" <?php selected( get_site_option('shibboleth_attribute_access'), 'standard' ); ?>>Environment Variables</option>
							<option value="redirect" <?php selected( get_site_option('shibboleth_attribute_access'), 'redirect' ); ?>>Redirected Environment Variables</option>
							<option value="http" <?php selected( get_site_option('shibboleth_attribute_access'), 'http' ); ?>>HTTP Headers</option>
						</select>
						<p><?php _e('By default, attributes passed from your Shibboleth Service Provider will be accessed using standard environment variables. '
						. 'For most users, leaving these defaults is perfectly fine. If you are running a special server configuration that results in environment variables '
						. 'being sent with the prefix <code>REDIRECT_</code>, you should select the "Redirected Environment Variables" option. If you are running '
						. 'your Shibboleth Service Provider on a reverse proxy, you should select the "HTTP Headers" option and, if at all possible, add a spoofkey below.', 'shibboleth') ?></p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="spoofkey"><?php _e('Spoof Key', 'shibboleth') ?></label></th>
					<td>
						<input type="text" id="spoofkey" name="spoofkey" value="<?php echo get_site_option('shibboleth_spoofkey') ?>" size="50" /><br />
						<p><?php _e('This option only applies when using the "HTTP Headers" attribute access method. For more details on setting a spoof key on the Shibboleth Service Provider, see <a href="https://wiki.shibboleth.net/confluence/display/SHIB2/NativeSPSpoofChecking">this wiki document</a>. '
						. '<br /><b>WARNING:</b> If you incorrectly set this option, you will force <b><i>ALL</i></b> attempts to authenticate with Shibboleth to fail.', 'shibboleth') ?></p>
					</td>
				</tr>
				<tr>
				<th scope="row"><label for="default_login"><?php _e('Shibboleth is default login', 'shibboleth') ?></label></th>
					<td>
						<input type="checkbox" id="default_login" name="default_login" <?php echo get_site_option('shibboleth_default_login') ? ' checked="checked"' : '' ?> />
						<label for="default_login"><?php _e('Use Shibboleth as the default login method for users.', 'shibboleth'); ?></label>

						<p><?php _e('If set, this will cause all standard WordPress login links to initiate Shibboleth'
							. ' login instead of local WordPress authentication.  Shibboleth login can always be'
							. ' initiated from the WordPress login form by clicking the "Login with Shibboleth" link.', 'shibboleth'); ?></p>
					</td>
				</tr>
				<tr>
				<th scope="row"><label for="auto_login"><?php _e('Shibboleth automatic login', 'shibboleth') ?></label></th>
					<td>
						<input type="checkbox" id="auto_login" name="auto_login" <?php echo get_site_option('shibboleth_auto_login') ? ' checked="checked"' : '' ?> />
						<label for="auto_login"><?php _e('Use Shibboleth to auto-login users.', 'shibboleth'); ?></label>

						<p><?php _e('If set, this will force a wp_signon() call and wp_safe_redirect()'
							. ' to the site_url option.' , 'shibboleth'); ?></p>
					</td>
				</tr>
<?php
	/**
	 * action shibboleth_options_table
	 * Add your own Shibboleth options items to the Shibboleth options table.
	 * Note: This is in a <table> so add a <tr> with appropriate styling.
	 *
	 * @param $shib_headers array
	 * @param $shib_roles array
	 * @since 1.4
	 */
	do_action( 'shibboleth_options_table', $shib_headers, $shib_roles );
?>
			</table>

			<br class="clear" />

			<h3><?php _e('User Profile Data', 'shibboleth') ?></h3>

			<p><?php _e('Define the Shibboleth headers which should be mapped to each user profile attribute.  These'
				. ' header names are configured in <code>attribute-map.xml</code> (for Shibboleth 2.x) or'
				. ' <code>AAP.xml</code> (for Shibboleth 1.x).', 'shibboleth') ?></p>

			<p>
				<?php _e('Wiki Documentation', 'shibboleth') ?>:
				<a href="https://spaces.internet2.edu/display/SHIB/AttributeAcceptancePolicy" target="_blank">Shibboleth 1.3</a> |
				<a href="https://spaces.internet2.edu/display/SHIB2/NativeSPAddAttribute" target="_blank">Shibboleth 2</a>
			</p>

			<table class="form-table optiontable editform" cellspacing="2" cellpadding="5">
				<tr valign="top">
					<th scope="row"><label for="username"><?php _e('Username') ?></label></th>
					<td><input type="text" id="username" name="headers[username][name]" value="<?php echo
						$shib_headers['username']['name'] ?>" /></td>
					<td width="60%"></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="first_name"><?php _e('First name') ?></label></th>
					<td><input type="text" id="first_name" name="headers[first_name][name]" value="<?php echo
						$shib_headers['first_name']['name'] ?>" /></td>
					<td><input type="checkbox" id="first_name_managed" name="headers[first_name][managed]" <?php
						if (isset($shib_headers['first_name']['managed'])) checked($shib_headers['first_name']['managed'], 'on') ?> /> <?php _e('Managed', 'shibboleth') ?></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="last_name"><?php _e('Last name') ?></label></th>
					<td><input type="text" id="last_name" name="headers[last_name][name]" value="<?php echo
						$shib_headers['last_name']['name'] ?>" /></td>
					<td><input type="checkbox" id="last_name_managed" name="headers[last_name][managed]" <?php
						if (isset($shib_headers['last_name']['managed'])) checked($shib_headers['last_name']['managed'], 'on') ?> /> <?php _e('Managed', 'shibboleth') ?></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="nickname"><?php _e('Nickname') ?></label></th>
					<td><input type="text" id="nickname" name="headers[nickname][name]" value="<?php echo
						$shib_headers['nickname']['name'] ?>" /></td>
					<td><input type="checkbox" id="nickname_managed" name="headers[nickname][managed]" <?php
						if (isset($shib_headers['nickname']['managed'])) checked($shib_headers['nickname']['managed'], 'on') ?> /> <?php _e('Managed', 'shibboleth') ?></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="_display_name"><?php _e('Display name', 'shibboleth') ?></label></th>
					<td><input type="text" id="_display_name" name="headers[display_name][name]" value="<?php echo
						$shib_headers['display_name']['name'] ?>" /></td>
					<td><input type="checkbox" id="display_name_managed" name="headers[display_name][managed]" <?php
						if (isset($shib_headers['display_name']['managed'])) checked($shib_headers['display_name']['managed'], 'on') ?> /> <?php _e('Managed', 'shibboleth') ?></td>
				</tr>
				<tr valign="top">
					<th scope="row"><label for="email"><?php _e('Email Address', 'shibboleth') ?></label></th>
					<td><input type="text" id="email" name="headers[email][name]" value="<?php echo
						$shib_headers['email']['name'] ?>" /></td>
					<td><input type="checkbox" id="email_managed" name="headers[email][managed]" <?php
						if (isset($shib_headers['email']['managed'])) checked($shib_headers['email']['managed'], 'on') ?> /> <?php _e('Managed', 'shibboleth') ?></td>
				</tr>
			</table>

			<p><?php _e('<em>Managed</em> profile fields are updated each time the user logs in using the current'
				. ' data provided by Shibboleth.  Additionally, users will be prevented from manually updating these'
				. ' fields from within WordPress.  Note that Shibboleth data is always used to populate the user'
				. ' profile during initial account creation.', 'shibboleth'); ?></p>

			<br class="clear" />

			<h3><?php _e('User Role Mappings', 'shibboleth') ?></h3>

<?php
/**
 * filter shibboleth_role_mapping_override
 * Return true to override the default user role mapping form
 *
 * @param boolean - default value false
 * @return boolean - true if override
 * @since 1.4
 *
 * Use in conjunction with shibboleth_role_mapping_form action below
 */
if ( apply_filters('shibboleth_role_mapping_override',false) === false ):
?>

			<p><?php _e('Users can be placed into one of WordPress\'s internal roles based on any'
				. ' attribute.  For example, you could define a special eduPersonEntitlement value'
				. ' that designates the user as a WordPress Administrator.  Or you could automatically'
				. ' place all users with an eduPersonAffiliation of "faculty" in the Author role.', 'shibboleth'); ?></p>

			<p><?php _e('<strong>Current Limitations:</strong> While WordPress supports users having'
				. ' multiple roles, the Shibboleth plugin will only place the user in the highest ranking'
				. ' role.  Only a single header/value pair is supported for each user role.  This may be'
				. ' expanded in the future to support multiple header/value pairs or regular expression'
				. ' values.  In the meantime, you can use the <em>shibboleth_roles</em> and'
				. ' <em>shibboleth_user_role</em> WordPress filters to provide your own logic for assigning'
				. ' user roles.', 'shibboleth'); ?></p>

			<style type="text/css">
				#role_mappings { padding: 0; }
				#role_mappings thead th { padding: 5px 10px; }
				#role_mappings td, #role_mappings th { border-bottom: 0px; }
			</style>

			<table class="form-table optiontable editform" cellspacing="2" cellpadding="5" width="100%">

				<tr>
					<th scope="row"><?php _e('Role Mappings', 'shibboleth') ?></th>
					<td id="role_mappings">
						<table id="">
						<col width="10%"></col>
						<col></col>
						<col></col>
						<thead>
							<tr>
								<th></th>
								<th scope="column"><?php _e('Header Name', 'shibboleth') ?></th>
								<th scope="column"><?php _e('Header Value', 'shibboleth') ?></th>
							</tr>
						</thead>
						<tbody>
<?php

					foreach ($wp_roles->role_names as $key => $name) {
						echo'
						<tr valign="top">
							<th scope="row">' . __($name) . '</th>
							<td><input type="text" id="role_'.$key.'_header" name="shibboleth_roles['.$key.'][header]" value="' . @$shib_roles[$key]['header'] . '" style="width: 100%" /></td>
							<td><input type="text" id="role_'.$key.'_value" name="shibboleth_roles['.$key.'][value]" value="' . @$shib_roles[$key]['value'] . '" style="width: 100%" /></td>
						</tr>';
					}
?>

						</tbody>
						</table>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php _e('Default Role', 'shibboleth') ?></th>
					<td>
						<select id="default_role" name="default_role">
						<option value="" <?php selected( get_site_option('shibboleth_default_role'), '' ); ?>><?php _e('(none)') ?></option>
<?php
			foreach ($wp_roles->role_names as $key => $name) {
				echo '
						<option value="' . $key . '"' .  selected( get_site_option('shibboleth_default_role'), $key ) . '>' . __($name) . '</option>';
			}
?>
						</select>

						<p><?php _e('If a user does not map into any of the roles above, they will'
							. ' be placed into the default role.  If there is no default role, the'
							. ' user will not be able to login with Shibboleth.', 'shibboleth'); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="update_roles"><?php _e('Update User Roles', 'shibboleth') ?></label></th>
					<td>
						<input type="checkbox" id="update_roles" name="update_roles" <?php echo get_site_option('shibboleth_update_roles') ? ' checked="checked"' : '' ?> />
						<label for="update_roles"><?php _e('Use Shibboleth data to update user role mappings each time the user logs in.', 'shibboleth') ?></label>

						<p><?php _e('Be aware that if you use this option, you should <strong>not</strong> update user roles manually,'
						. ' since they will be overwritten from Shibboleth the next time the user logs in.  Note that Shibboleth data'
					   	. ' is always used to populate the initial user role during account creation.', 'shibboleth') ?></p>

					</td>
				</tr>
			</table>

<?php
else:
	/**
	 * action shibboleth_role_mapping_form
	 * Roll your own custom Shibboleth role mapping admin UI
	 *
	 * @param $shib_headers array
	 * @param $shib_roles array
	 * @since 1.4
	 *
	 * Use in conjunction with shibboleth_role_mapping_override filter
	 */
	do_action( 'shibboleth_role_mapping_form', $shib_headers, $shib_roles );
endif; // if ( form override )
?>

			<?php wp_nonce_field('shibboleth_update_options') ?>
			<p class="submit"><input type="submit" name="submit" class="button-primary" value="<?php _e('Save Changes') ?>" /></p>
		</form>
	</div>

<?php
}
