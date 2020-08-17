<?php
/*
 Plugin Name: Shibboleth
 Plugin URI: http://wordpress.org/extend/plugins/shibboleth
 Description: Easily externalize user authentication to a <a href="http://shibboleth.internet2.edu">Shibboleth</a> Service Provider
 Author: Michael McNeill, mitcho (Michael 芳貴 Erlewine), Will Norris
 Version: 2.3
 Requires PHP: 5.6
 Requires at least: 4.0
 License: Apache 2 (http://www.apache.org/licenses/LICENSE-2.0.html)
 Text Domain: shibboleth
 */

define( 'SHIBBOLETH_MINIMUM_WP_VERSION', '4.0' );
define( 'SHIBBOLETH_MINIMUM_PHP_VERSION', '5.6');
define( 'SHIBBOLETH_PLUGIN_VERSION', '2.3' );

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
 * Determine if a constant is defined. If it is, return the value of the constant.
 * If it isn't, return the value from get_site_option(). If you'd like to pass a default
 * for get_site_option(), set $default to the requested default. If you'd like to check
 * for arrays in constants, set $array to true. If you'd like to return that the object
 * was obtained as a constant, set $compact to true and the result is an array. To get the
 * value of the constant or option, look at the value key. To check if the value was
 * retreived from a constant, look at the constant key.
 *
 * @since 2.1
 * @param string $option
 * @param bool $default
 * @param bool $array
 * @param bool $compact
 * @return mixed
 */
function shibboleth_getoption( $option, $default = false, $array = false, $compact = false ) {
	// If a constant is defined with the provided option name, get the value of the constant
	if ( defined( strtoupper( $option ) ) ) {
		$value = constant( strtoupper( $option ) );
		$constant = true;

		// In PHP 5.5 and below, we can't use arrays in constants, so we have to use
		// serialize and unserialize
		if ( $array && version_compare( PHP_VERSION, '5.6.0', '<' ) ) {
			$value = unserialize( $value );
		}
	// If no constant is set, just get the value from get_site_option()
	} else {
		$value = get_site_option( $option, $default );
		$constant = false;
	}

	// If compact is set to true, we compact $value and $constant together for easy use
	if ( $compact ) {
		return array( $value, $constant, 'value' => $value, 'constant' => $constant );
	// Otherwise, just return the $value
	} else {
		return $value;
	}
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
	// Get the specified shibboleth attribute access method; if one isn't specified
	// simply use standard environment variables since they're the safest
	$method = shibboleth_getoption( 'shibboleth_attribute_access_method', 'standard' );
	$fallback = shibboleth_getoption( 'shibboleth_attribute_access_method_fallback' );

	switch ( $method ) {
		// Use standard by default for security
		case 'standard' :
			$var_method = '';
			// Disable fallback to prevent the same variables from being checked twice.
			$fallback = false;
			break;
		// If specified, use redirect
		case 'redirect' :
			$var_method = 'REDIRECT_';
			break;
		// If specified, use http
		case 'http':
			$var_method = 'HTTP_';
			break;
		// If specified, use the custom specified method
		case 'custom':
			$custom = shibboleth_getoption( 'shibboleth_attribute_custom_access_method', '' );
			$var_method = $custom;
			break;
		// Otherwise, fall back to standard for security
		default :
			$var_method = '';
			// Disable fallback to prevent the same variables from being checked twice.
			$fallback = false;
	}

	// Using the selected attribute access method, check all possible cases
	$var_under = str_replace( '-', '_', $var );
	$var_upper = strtoupper( $var );
	$var_under_upper = strtoupper( $var_under );

	$check_vars = array(
		$var_method . $var => TRUE,
		$var_method . $var_under => TRUE,
		$var_method . $var_upper => TRUE,
		$var_method . $var_under_upper => TRUE,
	);

	// If fallback is enabled, we will add the standard environment variables to the end of the array to allow for fallback
	if ( $fallback ) {
		$fallback_check_vars = array(
			$var => TRUE,
			$var_under => TRUE,
			$var_upper => TRUE,
			$var_under_upper => TRUE,
		);

		$check_vars = array_merge( $check_vars, $fallback_check_vars );
	}

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
	$shibboleth_auto_login = shibboleth_getoption( 'shibboleth_auto_login' );

	if ( ! is_user_logged_in() && shibboleth_session_active( true ) && $shibboleth_auto_login ) {
		do_action( 'login_form_shibboleth' );

		$userobj = wp_signon( '', true );
		if ( ! is_wp_error( $userobj ) ) {
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
		wp_die( __( 'Shibboleth requires WordPress '. SHIBBOLETH_MINIMUM_WP_VERSION . ' or higher!', 'shibboleth' ) );
	} elseif ( version_compare( PHP_VERSION, SHIBBOLETH_MINIMUM_PHP_VERSION, '<' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die( __( 'Shibboleth requires PHP '. SHIBBOLETH_MINIMUM_PHP_VERSION . ' or higher!', 'shibboleth' ) );
	}

	if ( function_exists( 'switch_to_blog' ) ) {
		if ( is_multisite() ) {
			switch_to_blog( $GLOBALS['current_blog']->blog_id );
		} else {
			switch_to_blog( $GLOBALS['current_site']->blog_id );
		}
	}

	add_site_option( 'shibboleth_login_url', get_site_option( 'home' ) . '/Shibboleth.sso/Login' );
	add_site_option( 'shibboleth_default_to_shib_login', false );
	add_site_option( 'shibboleth_auto_login', false );
	add_site_option( 'shibboleth_logout_url', get_site_option( 'home' ) . '/Shibboleth.sso/Logout' );
	add_site_option( 'shibboleth_attribute_access_method', 'standard' );
	add_site_option( 'shibboleth_default_role', '' );
	add_site_option( 'shibboleth_update_roles', false );
	add_site_option( 'shibboleth_button_text', 'Log in with Shibboleth' );
	add_site_option( 'shibboleth_auto_combine_accounts', 'disallow' );
	add_site_option( 'shibboleth_manually_combine_accounts', 'disallow' );
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
	 * Changes to use plugin version instead of SVN revision.
	 *
	 * @since 1.8
	 */
	delete_site_option( 'shibboleth_plugin_revision' );

	/**
	 * Moves data from before version 1.9 to a new default role format,
	 * preventing a possible conflict with custom roles.
	 *
	 * @since 2.0
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

	/**
	 * Changes to support the shibboleth_getoption() function to match
	 * naming conventions of constants.
	 *
	 * @since 2.1
	 */
	$attribute_access = get_site_option( 'shibboleth_attribute_access' );
	if ( $attribute_access ) {
		update_site_option( 'shibboleth_attribute_access_method', $attribute_access );
		delete_site_option( 'shibboleth_attribute_access' );
	}
	$spoofkey = get_site_option( 'shibboleth_spoofkey' );
	if ( $spoofkey ) {
		update_site_option( 'shibboleth_spoof_key', $attribute_access );
		delete_site_option( 'shibboleth_spoofkey' );
	}
	$default_login = get_site_option( 'shibboleth_default_login' );
	if ( $default_login ) {
		update_site_option( 'shibboleth_default_to_shib_login', $default_login );
		delete_site_option( 'shibboleth_default_login' );
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
 * @return boolean|WP_Error
 * @since 1.3
 */
 function shibboleth_session_active( $auto_login = false ) {
 	$active = false;
	$method = shibboleth_getoption( 'shibboleth_attribute_access_method' );
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
		$spoofkey = shibboleth_getoption( 'shibboleth_spoof_key' );
		$shibboleth_auto_login = shibboleth_getoption( 'shibboleth_auto_login' );

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
		if ( isset( $_REQUEST['redirect_to'] ) ) {
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
	$password_reset_url = shibboleth_getoption( 'shibboleth_password_reset_url' );

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
	$default = shibboleth_getoption( 'shibboleth_default_to_shib_login' );

	if ( $default ) {
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
	$logout_url = shibboleth_getoption( 'shibboleth_logout_url' );

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
	if ( function_exists( 'switch_to_blog' ) ) {
		if ( !empty( $GLOBALS['current_blog']->blog_id ) && $GLOBALS['current_blog']->blog_id !== $GLOBALS['current_site']->site_id ) {
			switch_to_blog( $GLOBALS['current_blog']->blog_id );
		} else {
			switch_to_blog( $GLOBALS['current_site']->blog_id );
		}
	}

	$target = site_url( 'wp-login.php' );

	if ( function_exists( 'restore_current_blog' ) ) {
		restore_current_blog();
	}

	$target = add_query_arg( 'action', 'shibboleth', $target );
	if ( ! empty( $redirect ) ) {
		$target = add_query_arg( 'redirect_to', urlencode($redirect), $target );
	}

	// now build the Shibboleth session initiator URL
	$initiator_url = shibboleth_getoption( 'shibboleth_login_url' );

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
	$shib_headers = shibboleth_getoption( 'shibboleth_headers', array(), true );
	$shib_logging = shibboleth_getoption( 'shibboleth_logging', array(), true );
	$auto_combine_accounts = shibboleth_getoption( 'shibboleth_auto_combine_accounts' );
	$manually_combine_accounts = shibboleth_getoption( 'shibboleth_manually_combine_accounts' );

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

	// look up existing account by username, with email as a fallback
	$user_by = 'username';
	$user = get_user_by( 'login', $username );
	if ( ! $user ) {
		$user_by = 'email';
		$user = get_user_by( 'email', $email );
	}

	// if this account is not a Shibboleth account, then do account combine (if allowed)
	if ( is_object( $user ) && $user->ID && ! get_user_meta( $user->ID, 'shibboleth_account' ) ) {
		$do_account_combine = false;
		if ( $user_by === 'username' && ( $auto_combine_accounts === 'allow' || $manually_combine_accounts === 'allow' ) ) {
			$do_account_combine = true;
		} elseif ( $auto_combine_accounts === 'bypass' || $manually_combine_accounts === 'bypass' ) {
			$do_account_combine = true;
		}

		if ( $do_account_combine ) {
			update_user_meta( $user->ID, 'shibboleth_account', true );
			if ( in_array( 'account_merge', $shib_logging ) || defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[Shibboleth WordPress Plugin Logging] SUCCESS: User ' . $user->user_login . ' (ID: ' . $user->ID . ') merged accounts automatically.' );
			}
		} elseif ( $user_by === 'username' ) {
			if ( in_array( 'account_merge', $shib_logging ) || defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[Shibboleth WordPress Plugin Logging] ERROR: User ' . $user->user_login . ' (ID: ' . $user->ID . ') failed to automatically merge accounts. Reason: An account already exists with this username.' );
			}
			return new WP_Error( 'invalid_username', __( 'An account already exists with this username.', 'shibboleth' ) );
		} else {
			if ( in_array( 'account_merge', $shib_logging ) || defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[Shibboleth WordPress Plugin Logging] ERROR: User ' . $user->user_login . ' (ID: ' . $user->ID . ') failed to automatically merge accounts. Reason: An account already exists with this email.' );
			}
			return new WP_Error( 'invalid_email', __( 'An account already exists with this email.', 'shibboleth' ) );
		}
	}

	// create account if new user
	if ( ! $user ) {
		$user = shibboleth_create_new_user( $username, $email );
		if ( is_wp_error( $user ) ) return new WP_Error( $user->get_error_code(), $user->get_error_message() );
	}

	if ( ! $user ) {
		$error_message = 'Unable to create account based on data provided.';
		if ( in_array( 'account_create', $shib_logging ) || defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[Shibboleth WordPress Plugin Logging] ERROR: Unable to create account based on data provided.' );
		}
		return new WP_Error( 'missing_data', $error_message );
	}

	// update user data
	shibboleth_update_user_data( $user->ID );

	$update = shibboleth_getoption( 'shibboleth_update_roles' );

	if ( $update ) {
		$user_role = shibboleth_get_user_role();
		$user->set_role( $user_role );
		if ( in_array( 'role_update', $shib_logging ) || defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[Shibboleth WordPress Plugin Logging] SUCCESS: User ' . $user->user_login . ' (ID: ' . $user->ID . ') role was updated to ' . $user_role . '.' );
		}
		do_action( 'shibboleth_set_user_roles', $user );
	}

	if ( in_array( 'auth', $shib_logging ) || defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( '[Shibboleth WordPress Plugin Logging] SUCCESS: User ' . $user->user_login . ' (ID: ' . $user->ID . ') successfully authenticated.' );
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
	$create_accounts = shibboleth_getoption( 'shibboleth_create_accounts' );
	$shib_logging = shibboleth_getoption( 'shibboleth_logging', array(), true );
	$user_role = shibboleth_get_user_role();

	if ( $create_accounts != false ) {
		if ( empty( $user_login ) || empty( $user_email ) || $user_role === "_no_account" ) {
			return null;
		}

		// create account and flag as a shibboleth acount
		$user_id = wp_insert_user( array( 'user_login' => $user_login, 'user_email' => $user_email, 'user_pass' => NULL ) );
		if ( is_wp_error( $user_id ) ) {
			if ( in_array( 'account_create', $shib_logging ) || defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[Shibboleth WordPress Plugin Logging] ERROR: Unable to create account based on data provided. Reason: ' . $user_id->get_error_message() . '.' );
			}
	    	return new WP_Error( 'account_create_failed', $user_id->get_error_message() );
		} else {
			$user = new WP_User( $user_id );
			update_user_meta( $user->ID, 'shibboleth_account', true );

			// always update user data and role on account creation
			shibboleth_update_user_data( $user->ID, true );
			$user->set_role( $user_role );
			do_action( 'shibboleth_set_user_roles', $user );
			if ( in_array( 'account_create', $shib_logging ) || defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( '[Shibboleth WordPress Plugin Logging] SUCCESS: User ' . $user->user_login . ' (ID: ' . $user->ID . ') was created with role ' . ( $user_role ?: 'none' ) . '.' );
			}
			return $user;
		}
	} else {
		if ( in_array( 'auth', $shib_logging ) || defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[Shibboleth WordPress Plugin Logging] ERROR: User account does not exist and account creation is disabled.' );
		}
		return new WP_Error( 'no_access', __( 'You do not have sufficient access.' ) );
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
	if ( ! $wp_roles ) {
		$wp_roles = new WP_Roles();
	}

	$shib_roles = apply_filters( 'shibboleth_roles', shibboleth_getoption( 'shibboleth_roles', array(), true ) );
	$user_role = shibboleth_getoption( 'shibboleth_default_role' );

	foreach ( $wp_roles->role_names as $key => $name ) {
		if ( isset( $shib_roles[$key]['header'] ) ) {
			$role_header = $shib_roles[$key]['header'];
		}
		if ( isset( $shib_roles[$key]['value'] ) ) {
			$role_value = $shib_roles[$key]['value'];
		}
		if ( empty( $role_header ) || empty( $role_value ) ) {
			continue;
		}
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
	$shib_headers = shibboleth_getoption( 'shibboleth_headers', array(), true );

	$managed = array();

	foreach ( $shib_headers as $name => $value ) {
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
	$shib_headers = shibboleth_getoption( 'shibboleth_headers', array(), true );

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

	// Shibboleth users do not use their email address for authentication.
	add_filter( 'send_email_change_email', '__return_false' );

	wp_update_user( $user_data );
}


/**
 * Sanitize the nicename using sanitize_title
 *
 * @since 1.4
 * @see http://wordpress.org/support/topic/377030
 */
add_filter( 'shibboleth_user_nicename', 'sanitize_title' );

/**
 * Enqueues scripts and styles necessary for the Shibboleth button.
 *
 * @since 2.0
 */
function shibboleth_login_enqueue_scripts() {
	global $action;

	// Only add scripts for the login action to avoid breaking other forms.
	if ( $action === 'login' || $action === 'shibboleth' ) {
		wp_enqueue_style( 'shibboleth-login', plugins_url( 'assets/css/shibboleth_login_form.css', __FILE__ ), array( 'login' ), SHIBBOLETH_PLUGIN_VERSION );
		wp_enqueue_script( 'shibboleth-login', plugins_url( 'assets/js/shibboleth_login_form.js', __FILE__ ), array( 'jquery' ), SHIBBOLETH_PLUGIN_VERSION );
	}
}
add_action( 'login_enqueue_scripts', 'shibboleth_login_enqueue_scripts' );

/**
 * Prevents local WordPress authentication if disabled by an administrator.
 *
 * @since 2.0
 */
function shibboleth_disable_login() {
	$disable = shibboleth_getoption( 'shibboleth_disable_local_auth', false );

	$bypass = defined( 'SHIBBOLETH_ALLOW_LOCAL_AUTH' ) && SHIBBOLETH_ALLOW_LOCAL_AUTH;

	if ( $disable && ! $bypass ) {
		if ( isset( $_GET['action'] ) && $_GET['action'] === 'lostpassword' ) {
			// Disable the ability to reset passwords from wp-login.php
			add_filter( 'allow_password_reset', '__return_false' );
		} elseif ( isset( $_POST['log'] ) || isset( $_POST['user_login'] ) ) {
			// Disable the ability to login using local authentication
			wp_die( __( 'Shibboleth authentication is required.', 'shibboleth' ) );
		}
	}
}
add_action( 'login_init', 'shibboleth_disable_login' );

/**
 * Disables wp-login.php login form if disabled by an administrator.
 *
 * @since 2.0
 */
function shibboleth_disable_login_form() {
	$disable = shibboleth_getoption( 'shibboleth_disable_local_auth', false );
	$password_reset_url = shibboleth_getoption( 'shibboleth_password_reset_url', false );

	$bypass = defined( 'SHIBBOLETH_ALLOW_LOCAL_AUTH' ) && SHIBBOLETH_ALLOW_LOCAL_AUTH;

	if ( $disable && ! $bypass ) {
	?>
		<style type="text/css">
			.login #loginform p {
				display: none;
			}
			<?php if ( ! $password_reset_url ) { ?>
			.login #nav {
				display: none;
			}
			<?php } ?>
		</style>
	<?php
	}
}
add_action( 'login_enqueue_scripts', 'shibboleth_disable_login_form' );

/**
 * Updates the lost password URL, if specified.
 *
 * @param string $url original password reset URL
 * @since 2.1
 */
function shibboleth_custom_password_reset_url( $url ) {
	$password_reset_url = shibboleth_getoption( 'shibboleth_password_reset_url', false );

	if ( $password_reset_url ) {
		return $password_reset_url;
	} else {
		return $url;
	}
}
add_filter( 'lostpassword_url', 'shibboleth_custom_password_reset_url' );

/**
 * Add a "Log in with Shibboleth" link to the WordPress login form.  This link
 * will be wrapped in a <p> with an id value of "shibboleth_login" so that
 * deployers can style this however they choose.
 *
 * @since 1.0
 */
function shibboleth_login_form() {
	global $wp;
	$url = false;
	if ( isset( $wp->request ) ) {
		$url = wp_login_url( home_url( $wp->request ) );
	}
	$login_url = add_query_arg( 'action', 'shibboleth', $url );
	$login_url = remove_query_arg( 'reauth', $login_url );
	$button_text = shibboleth_getoption( 'shibboleth_button_text', 'Log in with Shibboleth' );
	$disable = shibboleth_getoption( 'shibboleth_disable_local_auth', false );
	?>
	<div id="shibboleth-wrap" <?php echo $disable ? 'style="margin-top:0;"' : '' ?>>
		<?php
		if ( ! $disable ) {
		?>
			<div class="shibboleth-or">
				<span><?php esc_html_e( 'Or', 'shibboleth' ); ?></span>
			</div>
		<?php
		}
		?>
		<a href="<?php echo esc_url( $login_url ); ?>" rel="nofollow" class="shibboleth-button button button-primary default">
			<span class="shibboleth-icon"></span>
			<?php esc_html_e( $button_text ); ?>
		</a>
	</div>
<?php
}
add_action( 'login_form', 'shibboleth_login_form' );


/**
 * Insert directives into .htaccess file to enable Shibboleth Lazy Sessions.
 *
 * @since 1.0
 */
function shibboleth_insert_htaccess() {
	$disabled = defined( 'SHIBBOLETH_DISALLOW_FILE_MODS' ) && SHIBBOLETH_DISALLOW_FILE_MODS;

	if ( got_mod_rewrite() && ! $disabled ) {
		$htaccess = get_home_path() . '.htaccess';
		$rules = array( '<IfModule mod_shib>', 'AuthType shibboleth', 'Require shibboleth', '</IfModule>', '<IfModule mod_shib.c>', 'AuthType shibboleth', 'Require shibboleth', '</IfModule>', '<IfModule mod_shib.cpp>', 'AuthType shibboleth', 'Require shibboleth', '</IfModule>' );
		insert_with_markers( $htaccess, 'Shibboleth', $rules );
	}
}


/**
 * Remove directives from .htaccess file to enable Shibboleth Lazy Sessions.
 *
 * @since 1.1
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
