=== Shibboleth ===
Contributors: michaelryanmcneill, willnorris, mitchoyoshitaka, jrchamp, dericcrago, bshelton229, Alhrath, dandalpiaz
Tags: shibboleth, authentication, login, saml
Requires at least: 4.0
Tested up to: 5.5
Requires PHP: 5.6
Stable tag: 2.3

Allows WordPress to externalize user authentication and account creation to a Shibboleth Service Provider.

== Description ==

This plugin is designed to support integrating your WordPress site into your existing identity management infrastructure using a [Shibboleth] Service Provider.

WordPress can be configured so that all standard login requests will be sent to your configured Shibboleth Identity Provider or Discovery Service.  Upon successful authentication, a new WordPress account will be automatically provisioned for the user if one does not already exist. User attributes (username, first name, last name, display name, nickname, and email address) can be synchronized with your enterprise's system of record each time the user logs into WordPress.

Finally, the user's role within WordPress can be automatically set (and continually updated) based on any attribute Shibboleth provides.  For example, you may decide to give users with an eduPersonAffiliation value of *faculty* the WordPress role of *editor*, while the eduPersonAffiliation value of *student* maps to the WordPress role *contributor*.  Or you may choose to limit access to WordPress altogether using a special eduPersonEntitlement value.

[Shibboleth]: http://shibboleth.internet2.edu/

= Contribute on GitHub =

This plugin is actively maintained by [michaelryanmcneill](https://profiles.wordpress.org/michaelryanmcneill) and the WordPress community, [using GitHub](https://github.com/michaelryanmcneill/shibboleth). Contributions are welcome, via pull request, [on GitHub](https://github.com/michaelryanmcneill/shibboleth). Issues can be submitted [on the issue tracker](https://github.com/michaelryanmcneill/shibboleth/issues).

== Installation ==

= Preface =

First and foremost, this plugin requires you to have a Shibboleth Service Provider installed and functional on your web server. This can be done many ways, but that is outside the scope of this plugin. Once you've configured the Shibboleth Service Provider, you can proceed with installing the plugin.

This plugin supports both "lazy sessions" (where requireSession is set to false) and "required sessions" (where requireSession is set to true).

Upon activation, the plugin will attempt to set the appropriate directives in WordPress's `.htaccess` file. You can prevent this from happening by defining the following `wp-config.php` constant:

    define('SHIBBOLETH_DISALLOW_FILE_MODS', true);

= Installation Process =

Visit "Plugins > Add New"
Search for "Shibboleth"
Activate the Shibboleth plugin from your Plugins page.
Configure the plugin from the Shibboleth settings page.

OR

Upload the "shibboleth" folder to the /wp-content/plugins/ directory
Activate the Shibboleth plugin from your Plugins page.
Configure the plugin from the Shibboleth settings page.

= Troubleshooting =

If for some reason the plugin is unable to add the appropriate directives for Shibboleth, you can add the following to your `.htaccess` file.

    AuthType shibboleth
    Require shibboleth

== Frequently Asked Questions ==

= What is Shibboleth? =

From [the Shibboleth Consortium](https://www.shibboleth.net/index/):

> Shibboleth is a standards based, open source software package for web single sign-on across or within organizational boundaries. It allows sites to make informed authorization decisions for individual access of protected online resources in a privacy-preserving manner.

= How do I configure a Shibboleth Service Provider? =

For more information on how to install the Native Shibboleth Service Provider on Linux, see [this wiki article](https://wiki.shibboleth.net/confluence/display/SHIB2/NativeSPLinuxInstall).

For more information on how to install the Native Shibboleth Service Provider on other operating systems, see [this wiki article](https://wiki.shibboleth.net/confluence/display/SHIB2/NativeSPInstall).

For more information on how to install Shibboleth on Nginx, see [this GitHub repo](https://github.com/nginx-shib/nginx-http-shibboleth).

Note, we cannot provide support for installation, configuration, or troubleshooting of Shibboleth Service Provider issues.

= Can I extend the Shibboleth plugin to provide custom logic? =

Yes, the plugin provides a number of new [actions][] and [filters][] that can be used to extend the functionality of the plugin.  Search `shibboleth.php` for occurrences of the function calls `apply_filters` and `do_action` to find them all.  Then [write a new plugin][] that makes use of the hooks.  If your require additional hooks to allow for extending other parts of the plugin, please notify the plugin authors via the [support forum][].

Before extending the plugin in this manner, please ensure that it is not actually more appropriate to add this logic to Shibboleth.  It may make more sense to add a new attribute to your Shibboleth Identity Provider's attribute store (e.g. LDAP directory), or a new attribute definition to the  Identity Provider's internal attribute resolver or the Shibboleth Service Provider's internal attribute extractor.  In the end, the Shibboleth administrator will have to make that call as to what is most appropriate.

[actions]: http://codex.wordpress.org/Plugin_API#Actions
[filters]: http://codex.wordpress.org/Plugin_API#Filters
[write a new plugin]: http://codex.wordpress.org/Writing_a_Plugin
[support forum]: http://wordpress.org/tags/shibboleth?forum_id=10#postform

= Can I control the plugin settings with constants in wp-config.php? =

Yes, the plugin allows for all settings to be controlled via constants in `wp-config.php`. If set, the constant will override the value that exists in the WordPress options table. The available constants are detailed (with their available options) below:

 - `SHIBBOLETH_ATTRIBUTE_ACCESS_METHOD`
   - Format: string
   - Available options: `'standard'` for the default "Environment Variables" option, `'redirect'` for the "Redirected Environment Variables" option, and `'http'` for the "HTTP Headers" option.
   - Example: `define('SHIBBOLETH_ATTRIBUTE_ACCESS_METHOD', 'standard');`
 - `SHIBBOLETH_ATTRIBUTE_ACCESS_METHOD_FALLBACK`
   - Format: boolean
   - Available options: `true` to fallback to the standard "Environment Variables" options when the selected attribute access method does not return results or `false` to not fallback.
   - Example: `define('SHIBBOLETH_ATTRIBUTE_ACCESS_METHOD_FALLBACK', true);`
 - `SHIBBOLETH_LOGIN_URL`
   - Format: string
   - Avaliable Options: none
   - Example: `define('SHIBBOLETH_LOGIN_URL', 'https://example.com/Shibboleth.sso/Login');`
 - `SHIBBOLETH_LOGOUT_URL`
   - Format: string
   - Avaliable Options: none
   - Example: `define('SHIBBOLETH_LOGOUT_URL', 'https://example.com/Shibboleth.sso/Logout');`
 - `SHIBBOLETH_PASSWORD_CHANGE_URL`
   - Format: string
   - Available options: none
   - Example: `define('SHIBBOLETH_PASSWORD_CHANGE_URL', 'https://sso.example.com/account/update');`
 - `SHIBBOLETH_PASSWORD_RESET_URL`
   - Format: string
   - Available options: none
   - Example: `define('SHIBBOLETH_PASSWORD_RESET_URL', 'https://sso.example.com/account/reset');`
 - `SHIBBOLETH_SPOOF_KEY`
   - Format: string
   - Available options: none
   - Example: `define('SHIBBOLETH_SPOOF_KEY', 'abcdefghijklmnopqrstuvwxyz');`
 - `SHIBBOLETH_DEFAULT_TO_SHIB_LOGIN`
   - Format: boolean
   - Available options: `true` to automatically default to Shibboleth login or `false` to not default to Shibboleth login.
   - Example: `define('SHIBBOLETH_DEFAULT_TO_SHIB_LOGIN', true);`
 - `SHIBBOLETH_AUTO_LOGIN`
   - Format: boolean
   - Available options: `true` to automatically login users with an existing Shibboleth session or `false` to not check for an existing Shibboleth session.
   - Example: `define('SHIBBOLETH_AUTO_LOGIN', true);`
 - `SHIBBOLETH_BUTTON_TEXT`
   - Format: string
   - Available options: none
   - Example: `define('SHIBBOLETH_BUTTON_TEXT', 'Login with Shibboleth');`
 - `SHIBBOLETH_DISABLE_LOCAL_AUTH`
   - Format: boolean
   - Available options: `true` to prevent users logging in using WordPress local authentication or `false` allow WordPress local authentication AND Shibboleth authentication.
   - Example: `define('SHIBBOLETH_DISABLE_LOCAL_AUTH', true);`
 - `SHIBBOLETH_HEADERS`
   - Format: array (>= PHP 5.6) OR serialized string (< PHP 5.6)
   - Available options: none
   - PHP 5.5 (and earlier) example: `define( 'SHIBBOLETH_HEADERS', serialize( array( 'username' => array( 'name' => 'eppn' ), 'first_name' => array( 'name' => 'givenName', 'managed' => 'on' ), 'last_name' => array( 'name' => 'sn', 'managed' => 'on' ), 'nickname' => array( 'name' => 'eppn', 'managed' => 'off' ), 'display_name' => array( 'name' => 'displayName', 'managed' => 'off' ), 'email' => array( 'name' => 'mail', 'managed' => 'on' ) ) ) );`
   - PHP 5.6 (and above) example: `const SHIBBOLETH_HEADERS = array( 'username' => array( 'name' => 'eppn' ), 'first_name' => array( 'name' => 'givenName', 'managed' => 'on' ), 'last_name' => array( 'name' => 'sn', 'managed' => 'on' ), 'nickname' => array( 'name' => 'eppn', 'managed' => 'off' ), 'display_name' => array( 'name' => 'displayName', 'managed' => 'off' ), 'email' => array( 'name' => 'mail', 'managed' => 'on' ) );`
   - PHP 7.0 (and above) example: `define('SHIBBOLETH_HEADERS', array( 'username' => array( 'name' => 'eppn' ), 'first_name' => array( 'name' => 'givenName', 'managed' => 'on' ), 'last_name' => array( 'name' => 'sn', 'managed' => 'on' ), 'nickname' => array( 'name' => 'eppn', 'managed' => 'off' ), 'display_name' => array( 'name' => 'displayName', 'managed' => 'off' ), 'email' => array( 'name' => 'mail', 'managed' => 'on' ) ) );`
 - `SHIBBOLETH_CREATE_ACCOUNTS`
   - Format: boolean
   - Available options: `true` to automatically create new users if they do not exist in the WordPress database or `false` to only allow existing users to authenticate.
   - Example: `define('SHIBBOLETH_CREATE_ACCOUNTS', true);`
 - `SHIBBOLETH_AUTO_COMBINE_ACCOUNTS`
   - Format: string
   - Available options: `'disallow'` for the default "Prevent Automatic Account Merging" option, `'allow'` for the "Allow Automatic Account Merging" option, and `'bypass'` for the "Allow Automatic Account Merging (Bypass Username Management)" option.
   - Example: `define('SHIBBOLETH_AUTO_COMBINE_ACCOUNTS', 'disallow');`
 - `SHIBBOLETH_MANUALLY_COMBINE_ACCOUNTS`
   - Format: string
   - Available options: `'disallow'` for the default "Prevent Manual Account Merging" option, `'allow'` for the "Allow Manual Account Merging" option, and `'bypass'` for the "Allow Manual Account Merging (Bypass Username Management)" option.
   - Example: `define('SHIBBOLETH_MANUALLY_COMBINE_ACCOUNTS', 'disallow');`
 - `SHIBBOLETH_ROLES`
   - Format: array (>= PHP 5.6) OR serialized string (< PHP 5.6)
   - Available options: none
   - PHP 5.5 (and earlier) example: `define( 'SHIBBOLETH_ROLES', serialize( array( 'administrator' => array( 'header' => 'entitlement', 'value' => 'urn:mace:example.edu:entitlement:wordpress:admin' ), 'author' => array( 'header' => 'affiliation', 'value' => 'faculty' ) ) ) );`
   - PHP 5.6 (and above) example: `const SHIBBOLETH_ROLES = array( 'administrator' => array( 'header' => 'entitlement', 'value' => 'urn:mace:example.edu:entitlement:wordpress:admin' ), 'author' => array( 'header' => 'affiliation', 'value' => 'faculty' ) );`
   - PHP 7.0 (and above) example: `define('SHIBBOLETH_ROLES', array( 'administrator' => array( 'header' => 'entitlement', 'value' => 'urn:mace:example.edu:entitlement:wordpress:admin' ), 'author' => array( 'header' => 'affiliation', 'value' => 'faculty' ) ) );`
 - `SHIBBOLETH_DEFAULT_ROLE`
   - Format: string
   - Available options: All available WordPress roles. The defaults are `'administrator'`, `'subscriber'`, `'author'`, `'editor'`, and `'contributor'`. Leave this constant empty `''` to make the default no allowed access.
   - Example: `define('SHIBBOLETH_DEFAULT_ROLE', 'subscriber');`
 - `SHIBBOLETH_UPDATE_ROLES`
   - Format: boolean
   - Available options: `true` to automatically use Shibboleth data to update user role mappings each time the user logs in or `false` to only update role mappings when a user is initally created.
   - Example: `define('SHIBBOLETH_UPDATE_ROLES', true);`
 - `SHIBBOLETH_LOGGING`
   - Format: array (>= PHP 5.6) OR serialized string (< PHP 5.6)
   - Available options: account_merge, account_create, auth, role_update
   - PHP 5.5 (and earlier) example: `define( 'SHIBBOLETH_LOGGING', serialize( array( 'account_merge', 'account_create', 'auth', 'role_update' ) ) );`
   - PHP 5.6 (and above) example: `const SHIBBOLETH_LOGGING = array( 'account_merge', 'account_create', 'auth', 'role_update' );`
   - PHP 7.0 (and above) example: `define('SHIBBOLETH_LOGGING', array( 'account_merge', 'account_create', 'auth', 'role_update' ) );`
 - `SHIBBOLETH_DISALLOW_FILE_MODS`
   - Format: boolean
   - Available options: `true` to disable the Shibboleth plugin from attempting to add `.htaccess` directives or `false` to allow the Shibboleth plugin to add the necessary `.htaccess` directives.
   - Example: `define('SHIBBOLETH_DISALLOW_FILE_MODS', true);`

== Screenshots ==

1. Configure login, logout, and password management URLs
2. Specify which Shibboleth headers map to user profile fields
3. Assign users into WordPress roles based on arbitrary data provided by Shibboleth

== Upgrade Notice ==
= 2.3 =
This update increases the minimum PHP version to 5.6 and the minimum WordPress version to 4.0. The plugin will fail to activate if you are running below those minimum versions. 

= 2.2.2 =
This update re-implements a previously reverted <IfModule> conditional for three aliases of the Shibboleth Apache module: `mod_shib`, `mod_shib.c`, and `mod_shib.cpp`. If you run into issues related to this change, please open an issue on [GitHub](https://github.com/michaelryanmcneill/shibboleth/issues).

= 2.0.2 =
This update brings with it a major change to the way Shibboleth attributes are accessed from versions less than 2.0. For most users, no additional configuration will be necessary. If you are using a specialized server configuration, such as a Shibboleth Service Provider on a reverse proxy or a server configuration that results in environment variables being sent with the prefix REDIRECT_, you should see the changelog for additional details: https://wordpress.org/plugins/shibboleth/#developers

= 2.0.1 =
This update brings with it a major change to the way Shibboleth attributes are accessed from versions less than 2.0. For most users, no additional configuration will be necessary. If you are using a specialized server configuration, such as a Shibboleth Service Provider on a reverse proxy or a server configuration that results in environment variables being sent with the prefix REDIRECT_, you should see the changelog for additional details: https://wordpress.org/plugins/shibboleth/#developers

= 2.0 =
This update brings with it a major change to the way Shibboleth attributes are accessed. For most users, no additional configuration will be necessary. If you are using a specialized server configuration, such as a Shibboleth Service Provider on a reverse proxy or a server configuration that results in environment variables being sent with the prefix REDIRECT_, you should see the changelog for additional details: https://wordpress.org/plugins/shibboleth/#developers

== Changelog ==
= version 2.3 (2020-08-17) =
 - Implementing a fallback option for the "Shibboleth Attribute Access Method". For example, if your web server returns redirected environment variables, but occasionally returns standard environment variables, you would want to enable this option. 
 - Removing deprecated `create_function()` from use. 
 - Bumped minimum PHP and WordPress versions to 5.6 and 4.0 respectively. 
 - Greatly improved the handling of managed fields and cleaned up `options-user.php`.  

= version 2.2.2 (2020-06-22) =
 - Re-implementing <IfModule> conditional for .htaccess to protect against the Shibboleth Apache module not being installed; [thanks to @jrchamp for reporting](https://github.com/michaelryanmcneill/shibboleth/issues/60). This change includes conditionals for `mod_shib`, `mod_shib.c`, and `mod_shib.cpp`. If you run into issues related to this change, please open an issue on [GitHub](https://github.com/michaelryanmcneill/shibboleth/issues).

= version 2.2.1 (2020-06-18) =
 - Temporarily reverts <IfModule> conditional for .htaccess due to [reported issues with cPanel environments](https://github.com/michaelryanmcneill/shibboleth/issues/64).

= version 2.2 (2020-06-17) =
 - Implementing <IfModule> conditional for .htaccess to protect against the Shibboleth Apache module not being installed; [thanks to @jrchamp for reporting](https://github.com/michaelryanmcneill/shibboleth/issues/60).
 - Added an option to disable account creation if no mapped roles or default roles exist; props [@dandalpiaz](https://github.com/michaelryanmcneill/shibboleth/pull/59).
 - Improve the Shibboleth login link so that when it shows up on a normal request it will correctly still be a login link and will redirect back to the page that showed the login link; props [@Alhrath](https://github.com/michaelryanmcneill/shibboleth/pull/53).

= version 2.1.1 (2018-05-16) =
 - Minor code cleanup for disabling authentication and passsword resets; props [@jrchamp](https://github.com/michaelryanmcneill/shibboleth/commit/06c28bec6d42e92a9338961e2f7ed4a7ae8a0f71#commitcomment-29005081).
 - Resolved a minor problem where setting the SHIBBOLETH_LOGGING constant on PHP 5.5 or below would not work in the administrative interface; props [@jrchamp](https://github.com/michaelryanmcneill/shibboleth/pull/47#discussion_r188758184).
 - Resolved an issue with the default to shibboleth login option in the admin; [thanks to @trandrew for reporting](https://github.com/michaelryanmcneill/shibboleth/issues/48).

= version 2.1 (2018-05-16) =
 - Resolved an issue where in multisite users could inadvertently be sent to an unrelated subsite after logging in; [thanks to @themantimeforgot for reporting](https://github.com/michaelryanmcneill/shibboleth/issues/33) and [props to @jrchamp for the fix](https://github.com/michaelryanmcneill/shibboleth/pull/35).
 - Resolved an regression that prevented users from authenticating if shibboleth_default_role is blank and shibboleth_create_accounts is enabled; props [@jrchamp](https://github.com/michaelryanmcneill/shibboleth/pull/37).
 - Cleaned up the shibboleth_authenticate_user function; props [@jrchamp](https://github.com/michaelryanmcneill/shibboleth/pull/38).
 - Allowed translate.wordpress.org compatibility; [thanks to @eric-gagnon for reporting](https://github.com/michaelryanmcneill/shibboleth/issues/41) and [props to @jrchamp for the fix](https://github.com/michaelryanmcneill/shibboleth/pull/42).
 - Resolved a conflict that caused the lost password and reset password forms to break; props [@jrchamp](https://github.com/michaelryanmcneill/shibboleth/pull/44).
 - Resolves an issue where the password reset URL wasn't being properly displayed on wp-login.php; [thanks to @earnjam for reporting](https://github.com/michaelryanmcneill/shibboleth/issues/28).
 - Prevents local password resets if local authentication is disabled; [thanks to @earnjam for reporting](https://github.com/michaelryanmcneill/shibboleth/issues/28).
 - Prevents local password changes if local authentication is disabled; [thanks to @earnjam for reporting](https://github.com/michaelryanmcneill/shibboleth/issues/28).
 - Standardized the way we check if options are set as constants to prevent duplicate code.
 - For manual account merges, ensure that email comparisons are case insensitive; [thanks to @mrbrown8 for reporting](https://github.com/michaelryanmcneill/shibboleth/issues/39).
 - Introduces available logging for various actions the plugin takes.

= version 2.0.2 (2018-01-17) =
 - Resolved an issue that caused manual linking of accounts to fail if user's didn't have an existing Shibboleth session. 

= version 2.0.1 (2018-01-17) =
 - Resolved a regression that prevented accounts from being created if they matched a group; [thanks to @Androclese for reporting](https://github.com/michaelryanmcneill/shibboleth/issues/22).
 - Resolved an issue where assets were not being properly included in the WordPress.org packaged plugin. 

= version 2.0 (2018-01-16) =
 - Changed the way we check for Shibboleth attributes. Now, by default, we only check standard environment variables for Shibboleth attributes. For most users, no additional configuration will be necessary. If you are using a specialized server configuration, such as a Shibboleth Service Provider on a reverse proxy or a server configuration that results in environment variables being sent with the prefix REDIRECT_, you should instead select the option specific to your server configuration. Selecting the "Redirected Environment Variables" option will look for attributes in environment variables prefixed with `REDIRECT_` while selecting the "HTTP Headers" option will look for attributes in environment variables (populated by HTTP Headers) prefixed with `HTTP_`. Most users should be fine leaving the default option selected; [thanks to @jrchamp for reporting](https://github.com/michaelryanmcneill/shibboleth/issues/8).
 - Changed the default behavior to not automatically update user roles.
 - Allow options to be defined via constants. Documentation has been added to the ["FAQ" section of the WordPress.org plugins page](https://wordpress.org/plugins/shibboleth/#can-i-control-the-plugin-settings-with-constants-in-wpconfigphp).
 - Allow automatic and manual merging of local WordPress accounts with Shibboleth accounts. This prevents a collision from occurring if the Shibboleth email attribute matches an email that already exists in the `wp_users` table. This is configurable by an administrator.
 - Changed the options page to utilize a more modern design centered around tabs.
 - Added signifcant customizations to the login page to bring it more in-line with WordPress.com Single Sign On.
 - Disabled the sending of an email notifying user's that their email had changed when the Shibboleth plugin updates user attributes to prevent user confusion; props [@jrchamp](https://github.com/michaelryanmcneill/shibboleth/pull/19).
 - Removed the `shibboleth-mu.php` file as it is no longer relevant.

= version 1.8.1 (2017-09-08) =
 - Use sanitize_title rather than sanitize_user to sanitize user_nicename; props [@jrchamp](https://github.com/michaelryanmcneill/shibboleth/pull/4).
 - Changed activation and deactivation hooks to use `__FILE__`; props [@jrchamp](https://github.com/michaelryanmcneill/shibboleth/pull/5).
 - Reverted to using `$_SERVER` in `shibboleth_getenv()` to handle use cases where `getenv()` doesn't return data; [thanks to @jmdemuth for reporting](https://github.com/michaelryanmcneill/shibboleth/issues/7).

= version 1.8 (2017-08-23) =
The Shibboleth plugin is now being maintained by [michaelryanmcneill](https://profiles.wordpress.org/michaelryanmcneill). Contributions are welcome on [GitHub](https://github.com/michaelryanmcneill/shibboleth)!

 - Adding the ability to disable `.htaccess` modifications with a `wp-config.php` constant (`SHIBBOLETH_DISALLOW_FILE_MODS`).
 - Added `shibboleth_getenv()` to support various prefixed environment variables from Shibboleth, including`REDIRECT_` and `HTTP_`; props [@cjbnc and @jrchamp](https://github.com/mitcho/shibboleth/pull/13).
 - Update various deprecated WordPress functions, including `update_usermeta()` and `get_userdatabylogin()`; props [@skoranda](https://github.com/mitcho/shibboleth/pull/21).
 - Resolved undefined index when calling `shibboleth_session_initiator_url()`; props [@skoranda](https://github.com/mitcho/shibboleth/pull/21).
 - Added support for PHP 7.x; props to many people.
 - Added `shibboleth_authenticate_user` filter; props [@boonebgorges](https://github.com/mitcho/shibboleth/pull/29).
 - Resolved undefined index on `admin-options.php`; props [@HirotoKagotani](https://github.com/mitcho/shibboleth/pull/31), [@jrchamp, and @stepmeul](https://github.com/mitcho/shibboleth/pull/23).
 - Resolved HTML markup mistake; [props @HirotoKagotani](https://github.com/mitcho/shibboleth/pull/31).
 - Adds an update success message to let user's know their settings were saved, using the Settings API.

= version 1.7 (2016-03-20) =
 - fixed a security vulnerability reported by WordPress security team
 - load multisite options correctly; [thanks to jdelsemme for reporting](https://github.com/mitcho/shibboleth/issues/8)
 - updated htaccess setting strings; [props dericcrago](https://github.com/mitcho/shibboleth/pull/6)
 - fix reauth loop; [props jrchamp](https://github.com/mitcho/shibboleth/pull/5)
 - set l10n text domain; [props jrchamp](https://github.com/mitcho/shibboleth/pull/5)

= version 1.6 (2014-04-07) =
 - tested for compatibility with recent WordPress versions; now requires WordPress 3.3
 - options screen now limited to admins; [props billjojo](https://github.com/mitcho/shibboleth/pull/1)
 - new option to auto-login using Shibboleth; [props billjojo](https://github.com/mitcho/shibboleth/pull/1)
 - remove workaround for MU `add_site_option`; [props billjojo](https://github.com/mitcho/shibboleth/pull/2)

= version 1.5 (2012-10-01) =
 - [Bugfix](http://wordpress.org/support/topic/plugin-shibboleth-loop-wrong-key-checked): check for `Shib_Session_ID` as well as `Shib-Session-ID` out of the box. Props David Smith

= version 1.4 (2010-08-30) =
 - tested for compatibility with WordPress 3.0
 - new hooks for developers to override the default user role mapping controls
 - now applies `sanitize_name()` to the Shibboleth user's `nicename` column

= version 1.3 (2009-10-02) =
 - required WordPress version bumped to 2.8
 - much cleaner integration with WordPress authentication system
 - individual user profile fields can be designated as managed by Shibboleth
 - start of support for i18n.  If anyone is willing to provide translations, please contact the plugin author

= version 1.2 (2009-04-21) =
 - fix bug where shibboleth users couldn't update their profile. (props pchapman on bug report)
 - fix bug where local logins were being sent to shibboleth

= version 1.1 (2009-03-16) =
 - cleaner integration with WordPress login form (now uses a custom action instead of hijacking the standard login action)
 - add option for enterprise password change URL -- shown on user profile page.
 - add option for enterprise password reset URL -- Shibboleth users are auto-redirected here if attempt WP password reset.
 - add plugin deactivation hook to remove .htaccess rules
 - add option to specify Shibboleth header for user nickname
 - add filters for all user attributes and user role (allow other plugins to override these values)
 - much cleaner interface on user edit admin page
 - fix bug with options being overwritten in WordPress MU

= version 1.0 (2009-03-14) =
 - now works properly with WordPress MU
 - move Shibboleth menu to Site Admin for WordPress MU (props: Chris Bland)
 - lots of code cleanup and documentation

= version 0.1 =
 - initial public release
