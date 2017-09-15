<?php
/*
 Plugin Name: Shibboleth
 Plugin URI: http://wordpress.org/extend/plugins/shibboleth
 Description: Easily externalize user authentication to a <a href="http://shibboleth.internet2.edu">Shibboleth</a> Service Provider
 Author: Will Norris, mitcho (Michael 芳貴 Erlewine), Michael McNeill
 Version: 1.9-alpha
 License: Apache 2 (http://www.apache.org/licenses/LICENSE-2.0.html)
 */

define( 'SHIBBOLETH_MINIMUM_WP_VERSION', '3.3' );
define( 'SHIBBOLETH_PLUGIN_VERSION', '1.9-alpha' );

/**
 * Determine if this is a new install or upgrade and, if so, run the
 * shibboleth_activate_plugin() function.
 *
 * @since 1.0
 */
$plugin_version = get_site_option( 'shibboleth_plugin_version', '0' );
if ( SHIBBOLETH_PLUGIN_VERSION != $plugin_version ) {
	add_action( 'admin_init', 'shibboleth_activate_plugin' );
}

/**
 * HTTP and FastCGI friendly getenv() replacement that handles
 * standard and REDIRECT_ environment variables, as well as HTTP
 * headers. Users select which method to use to allow for the most
 * secure configuration possible.
 *
 * @since 1.8
 * @param string $var
 * @return string|bool
 */
function shibboleth_getenv( $var ) {
	$method = get_site_option( 'shibboleth_attribute_access', 'standard' );

	switch ( $method ) {
		case 'standard' :
			$var_method = '';
			break;
		case 'redirect' :
			$var_method = 'REDIRECT_';
			break;
		case 'http':
			$var_method = 'HTTP_';
			break;
	}

	$var_under = str_replace( '-', '_', $var );
	$var_upper = strtoupper( $var );
	$var_under_upper = strtoupper( $var_under );

	$check_vars = array(
		$var_method . $var => TRUE,
		$var_method . $var_under => TRUE,
		$var_method . $var_upper => TRUE,
		$var_method . $var_under_upper => TRUE,
	);

	foreach ( $check_vars as $check_var => $true ) {
		if ( isset( $_SERVER[$check_var] ) && ( $result = $_SERVER[$check_var] ) !== FALSE ) {
			return $result;
		}
	}

	return FALSE;
}

/**
 * Perform automatic login. This is based on the user not being logged in,
 * an active session and the option being set to true.
 *
 * @since 1.6
 */
function shibboleth_auto_login() {
	$shibboleth_auto_login = get_site_option( 'shibboleth_auto_login' );
	if ( ! is_user_logged_in() && shibboleth_session_active( true ) && $shibboleth_auto_login ) {
		do_action( 'login_form_shibboleth' );

		$userobj = wp_signon( '', true );
		if ( is_wp_error( $userobj ) ) {
			// TODO: Proper error return.
		} else {
			wp_safe_redirect( $_SERVER['REQUEST_URI'] );
			exit();
		}
	}
}
add_action( 'init', 'shibboleth_auto_login' );

/**
 * Activate the plugin.  This registers default values for all of the
 * Shibboleth options and attempts to add the appropriate mod_rewrite rules to
 * WordPress's .htaccess file.
 *
 * @since 1.0
 */
function shibboleth_activate_plugin() {
	if ( version_compare( $GLOBALS['wp_version'], SHIBBOLETH_MINIMUM_WP_VERSION, '<' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die( __( 'Shibboleth requires WordPress '. SHIBBOLETH_MINIMUM_WP_VERSION . 'or higher!', 'shibboleth' ) );
	}

	if ( function_exists( 'switch_to_blog' ) ) {
		if ( is_multisite() ) {
			switch_to_blog( $GLOBALS['current_blog']->blog_id );
		} else {
			switch_to_blog( $GLOBALS['current_site']->blog_id );
		}
	}

	add_site_option( 'shibboleth_login_url', get_site_option( 'home' ) . '/Shibboleth.sso/Login' );
	add_site_option( 'shibboleth_default_login', false );
	add_site_option( 'shibboleth_auto_login', false );
	add_site_option( 'shibboleth_logout_url', get_site_option( 'home' ) . '/Shibboleth.sso/Logout' );
	add_site_option( 'shibboleth_attribute_access', 'standard' );
	add_site_option( 'shibboleth_default_role', 'subscriber' );
	add_site_option( 'shibboleth_update_roles', false );
	add_site_option( 'shibboleth_button_text', 'Login with Shibboleth' );
	add_site_option( 'shibboleth_combine_accounts', 'disallow' );
	add_site_option( 'shibboleth_disable_local_auth', false );

	$headers = array(
		'username' => array( 'name' => 'eppn', 'managed' => 'on' ),
		'first_name' => array( 'name' => 'givenName', 'managed' => 'on' ),
		'last_name' => array( 'name' => 'sn', 'managed' => 'on' ),
		'nickname' => array( 'name' => 'eppn', 'managed' => 'off' ),
		'display_name' => array( 'name' => 'displayName', 'managed' => 'off' ),
		'email' => array( 'name' => 'mail', 'managed' => 'on' ),
	);
	add_site_option( 'shibboleth_headers', $headers );

	$roles = array(
		'administrator' => array(
			'header' => 'entitlement',
			'value' => 'urn:mace:example.edu:entitlement:wordpress:admin',
		),
		'author' => array(
			'header' => 'affiliation',
			'value' => 'faculty',
		)
	);
	add_site_option( 'shibboleth_roles', $roles );

	shibboleth_insert_htaccess();

	shibboleth_migrate_old_data();

	update_site_option( 'shibboleth_plugin_version', SHIBBOLETH_PLUGIN_VERSION );

	if ( function_exists( 'restore_current_blog' ) ) {
		restore_current_blog();
	}
}
register_activation_hook( __FILE__, 'shibboleth_activate_plugin' );

 /**
  * Cleanup .htaccess rules and delete the option shibboleth_plugin_version
	* on deactivation.
  *
  * @since 1.0
  */
 function shibboleth_deactivate_plugin() {
 	shibboleth_remove_htaccess();
 	delete_site_option( 'shibboleth_plugin_version' );
 }
 register_deactivation_hook( __FILE__, 'shibboleth_deactivate_plugin' );

/**
 * Migrate old (before version 1.9) data to a newer format that
 * doesn't allow the default role to be stored with the rest of
 * the role mappings.
 */
function shibboleth_migrate_old_data() {
	/**
	 * Moves data from before version 1.3 to a new header format,
	 * allowing each header to be marked as 'managed' individually
	 *
	 * @since 1.3
	 */
	$managed = get_site_option( 'shibboleth_update_users', 'off' );
	$headers = get_site_option( 'shibboleth_headers', array() );
	$updated = false;
	foreach ( $headers as $key => $value ) {
		if ( is_string($value) ) {
			$headers[$key] = array(
				'name' => $value,
				'managed' => $managed,
			);
			$updated = true;
		}
	}
	if ( $updated ) {
		update_site_option( 'shibboleth_headers', $headers );
	}
	delete_site_option( 'shibboleth_update_users' );

	/**
	 * Moves data from before version 1.9 to a new default role format,
	 * preventing a possible conflict with custom roles.
	 *
	 * @since 1.9
	 */
	$roles = get_site_option( 'shibboleth_roles', array() );
	if ( isset( $roles['default'] ) && $roles['default'] != '' ) {
		update_site_option( 'shibboleth_testing', '1' );
		update_site_option( 'shibboleth_default_role', $roles['default'] );
		update_site_option( 'shibboleth_create_accounts', true );
		unset( $roles['default'] );
		update_site_option( 'shibboleth_roles', $roles );
	} elseif ( isset( $roles['default'] ) && $roles['default'] === '' ) {
		update_site_option( 'shibboleth_testing', '2' );
		update_site_option( 'shibboleth_default_role', 'subscriber' );
		update_site_option( 'shibboleth_create_accounts', false );
		unset( $roles['default'] );
		update_site_option( 'shibboleth_roles', $roles );
	}
}

/**
 * Load Shibboleth admin hooks only on admin page loads.
 *
 * @since 1.3
 */
function shibboleth_admin_hooks() {
	if ( defined( 'WP_ADMIN' ) && WP_ADMIN === true ) {
		require_once dirname( __FILE__ ) . '/options-admin.php';
		require_once dirname( __FILE__ ) . '/options-user.php';
	}
}
add_action( 'init', 'shibboleth_admin_hooks' );

/**
 * Check if a Shibboleth session is active. If HTTP headers are being used
 * we do additional testing to see if a spoofkey needs to be vaildated.
 *
 * @uses apply_filters calls 'shibboleth_session_active' before returning final result
 * @param boolean $auto_login whether this is being triggered by an auto_login request or not
 * @var constant SHIBBOLETH_BYPASS_SPOOF_CHECKING set in wp-config.php to bypass spoofkey checking
 * @return boolean|WP_Error
 * @since 1.3
 */
 function shibboleth_session_active( $auto_login = false ) {
 	$active = false;
 	$method = get_site_option( 'shibboleth_attribute_access' );
 	$session = shibboleth_getenv( 'Shib-Session-ID' );

 	if ( $session && $method !== 'http' ) {
 		$active = true;
 	} elseif ( $session && $method === 'http' ) {
		/**
		 * Handling HTTP header cases with a spoofkey to better protect against
		 * HTTP header spoofing.
		 *
		 * @see https://wiki.shibboleth.net/confluence/display/SHIB2/NativeSPSpoofChecking
		 */
		$spoofkey = get_site_option( 'shibboleth_spoofkey' );
		$shibboleth_auto_login = get_site_option( 'shibboleth_auto_login' );

		if ( $spoofkey !== false && $spoofkey !== '' ) {
			$bypass = defined( 'SHIBBOLETH_BYPASS_SPOOF_CHECKING' ) && SHIBBOLETH_BYPASS_SPOOF_CHECKING;
			$checkkey = shibboleth_getenv( 'Shib-Spoof-Check' );
			if ( $checkkey == $spoofkey || $bypass ) {
				$active = true;
			} elseif ( $auto_login ) {
				$active = false;
			} else {
				wp_die( __( 'The Shibboleth request you submitted failed vaildation. Please contact your site administrator for further assistance.', 'shibboleth' ) );
			}
		} else {
			$active = true;
		}
	} else {
		$active = false;
	}

 	$active = apply_filters( 'shibboleth_session_active', $active );
 	return $active;
 }


/**
 * Authenticate the user using Shibboleth.  If a Shibboleth session is active,
 * use the data provided by Shibboleth to log the user in.  If a Shibboleth
 * session is not active, redirect the user to the Shibboleth Session Initiator
 * URL to initiate the session.
 *
 * @since 1.0
 */
function shibboleth_authenticate( $user, $username, $password ) {
	if ( shibboleth_session_active() ) {
		return shibboleth_authenticate_user();
	} else {
		if (isset( $_REQUEST['redirect_to'] )) {
			$initiator_url = shibboleth_session_initiator_url( $_REQUEST['redirect_to'] );
		} else {
			$initiator_url = shibboleth_session_initiator_url();
		}
		wp_redirect( $initiator_url );
		exit;
	}
}


/**
 * When wp-login.php is loaded with 'action=shibboleth', hook Shibboleth
 * into the WordPress authentication flow.
 *
 * @since 1.3
 */
function shibboleth_login_form_shibboleth() {
	add_filter( 'authenticate', 'shibboleth_authenticate', 10, 3 );
}
add_action( 'login_form_shibboleth', 'shibboleth_login_form_shibboleth' );


/**
 * If a Shibboleth user requests a password reset, and the Shibboleth password
 * reset URL is set, redirect the user there.
 *
 * @since 1.3
 */
function shibboleth_retrieve_password( $user_login ) {
	$password_reset_url = get_site_option( 'shibboleth_password_reset_url' );

	if ( ! empty( $password_reset_url ) ) {
		$user = get_user_by( 'login', $user_login );
		if ( $user && get_user_meta( $user->ID, 'shibboleth_account' ) ) {
			wp_redirect( $password_reset_url );
			exit;
		}
	}
}
add_action( 'retrieve_password', 'shibboleth_retrieve_password' );


/**
 * If Shibboleth is the default login method, add 'action=shibboleth' to the
 * WordPress login URL.
 *
 * @since 1.0
 */
function shibboleth_login_url( $login_url ) {
	if ( get_site_option( 'shibboleth_default_login' ) ) {
		$login_url = add_query_arg( 'action', 'shibboleth', $login_url );
	}
	return $login_url;
}
add_filter( 'login_url', 'shibboleth_login_url' );


/**
 * If the Shibboleth logout URL is set and the user has an active Shibboleth
 * session, log the user out of Shibboleth after logging them out of WordPress.
 *
 * @since 1.0
 */
function shibboleth_logout() {
	$logout_url = get_site_option( 'shibboleth_logout_url' );

	if ( ! empty( $logout_url ) && shibboleth_session_active() ) {
		wp_redirect( $logout_url );
		exit;
	}
}
add_action( 'wp_logout', 'shibboleth_logout', 20 );


/**
 * Generate the URL to initiate Shibboleth login.
 *
 * @param string $redirect the final URL to redirect the user to after all login is complete
 * @return the URL to direct the user to in order to initiate Shibboleth login
 * @uses apply_filters() Calls 'shibboleth_session_initiator_url' before returning session intiator URL
 * @since 1.3
 */
function shibboleth_session_initiator_url( $redirect = null ) {

	// first build the target URL.  This is the WordPress URL the user will be returned to after Shibboleth
	// is done, and will handle actually logging the user into WordPress using the data provdied by Shibboleth
	if ( function_exists( 'switch_to_blog' ) ) switch_to_blog( $GLOBALS['current_site']->blog_id );
	$target = site_url( 'wp-login.php' );
	if ( function_exists( 'restore_current_blog' ) ) restore_current_blog();

	$target = add_query_arg( 'action', 'shibboleth', $target );
	if ( ! empty( $redirect ) ) {
		$target = add_query_arg( 'redirect_to', urlencode($redirect), $target );
	}

	// now build the Shibboleth session initiator URL
	$initiator_url = get_site_option( 'shibboleth_login_url' );
	$initiator_url = add_query_arg( 'target', urlencode($target), $initiator_url );

	$initiator_url = apply_filters( 'shibboleth_session_initiator_url', $initiator_url );

	return $initiator_url;
}


/**
 * Authenticate the user based on the current Shibboleth headers.
 *
 * If the data available does not map to a WordPress role (based on the
 * configured role-mapping), the user will not be allowed to login.
 *
 * If this is the first time we've seen this user (based on the username
 * attribute), a new account will be created.
 *
 * Known users will have their profile data updated based on the Shibboleth
 * data present if the plugin is configured to do so.
 *
 * @return WP_User|WP_Error authenticated user or error if unable to authenticate
 * @since 1.0
 */
function shibboleth_authenticate_user() {
	$shib_headers = get_site_option( 'shibboleth_headers' );
	$combine_accounts = get_site_option( 'shibboleth_combine_accounts', 'disallow');

	// ensure user is authorized to login
	$user_role = shibboleth_get_user_role();

	if ( empty( $user_role ) ) {
		return new WP_Error( 'no_access', __( 'You do not have sufficient access.' ) );
	}

	$username = shibboleth_getenv( $shib_headers['username']['name'] );
	$email = shibboleth_getenv( $shib_headers['email']['name'] );

	/**
	 * Allows a bypass mechanism for native Shibboleth authentication.
	 *
	 * Returning a non-null value from this filter will result in your value being
	 * returned to WordPress. You can prevent a user from being authenticated
	 * by returning a WP_Error object.
	 *
	 * @param null   $auth
	 * @param string $username
	 */
	$authenticate = apply_filters( 'shibboleth_authenticate_user', null, $username );
	if ( null !== $authenticate ) {
		return $authenticate;
	}

	$user = get_user_by( 'login', $username );

	$combine_accounts = get_site_option( 'shibboleth_combine_accounts', 'disallow' );

	if ( $user->ID ) {
		if ( ! get_user_meta( $user->ID, 'shibboleth_account' ) ) {
			if ( $combine_accounts === 'allow' || $combine_accounts === 'bypass' ) {
				update_user_meta( $user->ID, 'shibboleth_account', true );
			} else {
				return new WP_Error( 'invalid_username', __( 'An account already exists with this username.', 'shibboleth' ) );
			}
		}
	} elseif ( ! $user->ID ) {
		$user = get_user_by( 'email', $email );
		if ( $user->ID && $combine_accounts === 'bypass' ) {
			update_user_meta( $user->ID, 'shibboleth_account', true );
		} else {
			return new WP_Error( 'invalid_email', __( 'An account already exists with this email.', 'shibboleth' ) );
		}
	}

	// create account if new user
	if ( ! $user ) {
		$user = shibboleth_create_new_user( $username, $email );
	}

	if ( ! $user ) {
		$error_message = 'Unable to create account based on data provided.';
		return new WP_Error( 'missing_data', $error_message );
	}

	// update user data
	update_user_meta( $user->ID, 'shibboleth_account', true );
	shibboleth_update_user_data( $user->ID );
	if ( get_site_option( 'shibboleth_update_roles' ) ) {
		$user->set_role( $user_role );
		do_action( 'shibboleth_set_user_roles', $user );
	}

	return $user;
}


/**
 * Create a new WordPress user account, and mark it as a Shibboleth account.
 *
 * @param string $user_login login name for the new user
 * @param string $user_email email address for the new user
 * @return object WP_User object for newly created user
 * @since 1.0
 */
function shibboleth_create_new_user( $user_login, $user_email ) {
	if ( empty( $user_login ) || empty( $user_email ) ) return null;

	// create account and flag as a shibboleth acount
	require_once( ABSPATH . WPINC . '/registration.php' );
	$user_id = wp_insert_user( array( 'user_login' => $user_login, 'user_email' => $user_email ) );
	if ( is_wp_error( $user_id ) ) {
    return new WP_Error( 'account_create_failed', $user_id->get_error_message() );
	} else {
		$user = new WP_User( $user_id );
		update_user_meta( $user->ID, 'shibboleth_account', true );

		// always update user data and role on account creation
		shibboleth_update_user_data( $user->ID, true );
		$user_role = shibboleth_get_user_role();
		$user->set_role( $user_role );
		do_action( 'shibboleth_set_user_roles', $user );

		return $user;
	}
}


/**
 * Get the role the current user should have.  This is determined by the role
 * mapping configured for the plugin, and the Shibboleth headers present at the
 * time of login.
 *
 * @return string the role the current user should have
 * @uses apply_filters() Calls 'shibboleth_roles' after retrieving shibboleth_roles array
 * @uses apply_filters() Calls 'shibboleth_user_role' before returning final user role
 * @since 1.0
 */
function shibboleth_get_user_role() {
	global $wp_roles;
	if ( !$wp_roles ) $wp_roles = new WP_Roles();

	$shib_roles = apply_filters( 'shibboleth_roles', get_site_option( 'shibboleth_roles' ) );
	$create_accounts = get_site_option( 'shibboleth_create_accounts' );
	if ( $create_accounts != false ) {
		$user_role = get_site_option( 'shibboleth_default_role' );
	} else {
		$user_role = false;
	}

	foreach ( $wp_roles->role_names as $key => $name ) {
		$role_header = $shib_roles[$key]['header'];
		$role_value = $shib_roles[$key]['value'];

		if ( empty( $role_header ) || empty( $role_value ) ) continue;

		$values = explode( ';', shibboleth_getenv( $role_header ) );
		if ( in_array( $role_value, $values ) ) {
			$user_role = $key;
			break;
		}
	}

	$user_role = apply_filters( 'shibboleth_user_role', $user_role );

	return $user_role;
}


/**
 * Get the user fields that are managed by Shibboleth.
 *
 * @return Array user fields managed by Shibboleth
 * @since 1.3
 */
function shibboleth_get_managed_user_fields() {
	$headers = get_site_option( 'shibboleth_headers' );
	$managed = array();

	foreach ( $headers as $name => $value ) {
		if ( isset( $value['managed'] ) ) {
			if ( $value['managed'] ) {
				$managed[] = $name;
			}
		}
	}

	return $managed;
}


/**
 * Update the user data for the specified user based on the current Shibboleth headers.  Unless
 * the 'force_update' parameter is true, only the user fields marked as 'managed' fields will be
 * updated.
 *
 * @param int $user_id ID of the user to update
 * @param boolean $force_update force update of user data, regardless of 'managed' flag on fields
 * @uses apply_filters() Calls 'shibboleth_user_*' before setting user attributes,
 *       where '*' is one of: login, nicename, first_name, last_name,
 *       nickname, display_name, email
 * @since 1.0
 */
function shibboleth_update_user_data( $user_id, $force_update = false ) {

	$shib_headers = get_site_option( 'shibboleth_headers' );

	$user_fields = array(
		'user_login' => 'username',
		'user_nicename' => 'username',
		'first_name' => 'first_name',
		'last_name' => 'last_name',
		'nickname' => 'nickname',
		'display_name' => 'display_name',
		'user_email' => 'email'
	);

	$user_data = array(
		'ID' => $user_id,
	);

	foreach ( $user_fields as $field => $header ) {
		$managed = false;
		if ( isset( $shib_headers[$header]['managed'] ) ) {
			$managed = $shib_headers[$header]['managed'];
		}
		if ( $force_update || $managed ) {
			$filter = 'shibboleth_' . ( strpos( $field, 'user_' ) === 0 ? '' : 'user_' ) . $field;
			$user_data[$field] = apply_filters( $filter, shibboleth_getenv( $shib_headers[$header]['name'] ) );
		}
	}

	wp_update_user( $user_data );
}


/**
 * Sanitize the nicename using sanitize_title
 * See discussion: http://wordpress.org/support/topic/377030
 *
 * @since 1.4
 */
add_filter( 'shibboleth_user_nicename', 'sanitize_title' );

/**
 * Add a "Login with Shibboleth" link to the WordPress login form.  This link
 * will be wrapped in a <p> with an id value of "shibboleth_login" so that
 * deployers can style this however they choose.
 *
 * @since 1.0
 */
function shibboleth_login_form() {
	$login_url = add_query_arg( 'action', 'shibboleth' );
	$login_url = remove_query_arg( 'reauth', $login_url );
	$button_text = get_site_option( 'shibboleth_button_text', 'Login with Shibboleth' );
	echo '<p id="shibboleth_login"><a href="' . esc_url( $login_url ) . '">' . esc_html( $button_text ) . '</a></p>';
}
add_action( 'login_form', 'shibboleth_login_form' );


/**
 * Insert directives into .htaccess file to enable Shibboleth Lazy Sessions.
 *
 * @since 1.0
 * @var constant SHIBBOLETH_DISALLOW_FILE_MODS set in wp-config.php to prevent .htaccess modifications
 */
function shibboleth_insert_htaccess() {
	$disabled = defined( 'SHIBBOLETH_DISALLOW_FILE_MODS' ) && SHIBBOLETH_DISALLOW_FILE_MODS;
	if ( got_mod_rewrite() && ! $disabled ) {
		$htaccess = get_home_path() . '.htaccess';
		$rules = array( 'AuthType shibboleth', 'Require shibboleth' );
		insert_with_markers( $htaccess, 'Shibboleth', $rules );
	}
}


/**
 * Remove directives from .htaccess file to enable Shibboleth Lazy Sessions.
 *
 * @since 1.1
 * @var constant SHIBBOLETH_DISALLOW_FILE_MODS set in wp-config.php to prevent .htaccess modifications
 */
function shibboleth_remove_htaccess() {
	$disabled = defined( 'SHIBBOLETH_DISALLOW_FILE_MODS' ) && SHIBBOLETH_DISALLOW_FILE_MODS;
	if ( got_mod_rewrite() && ! $disabled ) {
		$htaccess = get_home_path() . '.htaccess';
		insert_with_markers( $htaccess, 'Shibboleth', array() );
	}
}

/**
 * Load localization files.
 *
 * @since 1.7
 */
function shibboleth_load_textdomain() {
	load_plugin_textdomain( 'shibboleth', false, dirname( plugin_basename( __FILE__ ) ) . '/localization/' );
}
add_action( 'plugins_loaded', 'shibboleth_load_textdomain' );
