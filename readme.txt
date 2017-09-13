=== Shibboleth ===
Contributors: michaelryanmcneill, willnorris, mitchoyoshitaka
Tags: shibboleth, authentication, login, saml
Requires at least: 3.3
Tested up to: 4.8.1
Stable tag: 1.9-alpha

Allows WordPress to externalize user authentication and account creation to a
Shibboleth Service Provider.

== Description ==

This plugin is designed to support integrating your WordPress
site into your existing identity management infrastructure using a
[Shibboleth] Service Provider.

WordPress can be configured so that all standard login requests will be sent to
your configured Shibboleth Identity Provider or Discovery Service.  Upon
successful authentication, a new WordPress account will be automatically
provisioned for the user if one does not already exist. User attributes
(username, first name, last name, display name, nickname, and email address)
can be synchronized with your enterprise's system of record each time the user
logs into WordPress.

Finally, the user's role within WordPress can be automatically set (and
continually updated) based on any attribute Shibboleth provides.  For example,
you may decide to give users with an eduPersonAffiliation value of *faculty*
the WordPress role of *editor*, while the eduPersonAffiliation value of
*student* maps to the WordPress role *contributor*.  Or you may choose to limit
access to WordPress altogether using a special eduPersonEntitlement value.

[Shibboleth]: http://shibboleth.internet2.edu/

= Contribute on GitHub =

This plugin is actively maintained by the community, [using
GitHub](https://github.com/michaelryanmcneill/shibboleth). Contributions are welcome, via
pull request, [on GitHub](https://github.com/michaelryanmcneill/shibboleth). Issues can be
submitted [on the issue tracker](https://github.com/michaelryanmcneill/shibboleth/issues).

== Installation ==

First and foremost, you must have the Shibboleth Service Provider [properly
installed][] and working.  If you don't have Shibboleth working yet, I assure
you that you won't get this plugin to work.  This plugin expects Shibboleth to
be configured to use "lazy sessions", so ensure that you have Shibboleth
configured with requireSession set to "false".  Upon activation, the plugin
will attempt to set the appropriate directives in WordPress's .htaccess file.
If it is unable to do so, you can add this manually:

    AuthType shibboleth
    Require shibboleth

The option to automatically login the users into WordPress also works when not
using the lazy session options as it will force login into WordPress. In other
words, if the user has an active session and you are requiring authentication
to access this WordPress site and they need to be logged into WordPress, then
they will be logged in without having to use the WordPress login page.

This works very well for sites that use WordPress for internal ticketing and
helpdesk functions where any access to content requires authentication.
Consider the following .htaccess options when used in conjunction with the
automatic login feature

    AuthType shibboleth
    ShibRequestSetting requireSession 1
    Require valid-user

OR

    Authtype shibboleth
    ShibRequestSetting requireSession 1
    Require isMemberOf group1 group2
    Require sAMAccountName user1 user 2


NOTE: If the plugin is successful in updating your .htaccess file, it will
place the option between a marked block:

   BEGIN Shibboleth
   END Shibboleth

If you add more options, you may want to consider moving all configuration
options out of this block as they will be cleared out upon deactivation
of the plugin.

= For single-user WordPress =

Upload the `shibboleth` folder to your WordPress plugins folder (probably
`/wp-content/plugins`), and activate it through the WordPress admin panel.
Configure it from the Shibboleth settings page.

= For WordPress Multisite =

Upload the `shibboleth` folder to your `mu-plugins` folder
(probably `/wp-content/mu-plugins`).  Move the file `shibboleth-mu.php` from
the `shibboleth` folder up one directory so that it is in `mu-plugins`
alongside the `shibboleth` folder.  No need to activate it, just configure it
from the Shibboleth settings page, found under "Site Admin".

[properly installed]: https://spaces.internet2.edu/display/SHIB2/Installation

== Frequently Asked Questions ==

= What is Shibboleth? =

From [the Shibboleth homepage][]:

> The Shibboleth System is a standards based, open source software package for
> web single sign-on across or within organizational boundaries. It allows
> sites to make informed authorization decisions for individual access of
> protected online resources in a privacy-preserving manner.

[the Shibboleth homepage]: http://shibboleth.internet2.edu/

= Can I extend the Shibboleth plugin to provide custom logic? =

Yes, the plugin provides a number of new [actions][] and [filters][] that can
be used to extend the functionality of the plugin.  Search `shibboleth.php` for
occurrences of the function calls `apply_filters` and `do_action` to find them
all.  Then [write a new plugin][] that makes use of the hooks.  If your require
additional hooks to allow for extending other parts of the plugin, please
notify the plugin authors via the [support forum][].

Before extending the plugin in this manner, please ensure that it is not
actually more appropriate to add this logic to Shibboleth.  It may make more
sense to add a new attribute to your Shibboleth Identity Provider's attribute
store (e.g. LDAP directory), or a new attribute definition to the  Identity
Provider's internal attribute resolver or the Shibboleth Service Provider's
internal attribute extractor.  In the end, the Shibboleth administrator will
have to make that call as to what is most appropriate.

[actions]: http://codex.wordpress.org/Plugin_API#Actions
[filters]: http://codex.wordpress.org/Plugin_API#Filters
[write a new plugin]: http://codex.wordpress.org/Writing_a_Plugin
[support forum]: http://wordpress.org/tags/shibboleth?forum_id=10#postform

== Screenshots ==

1. Configure login, logout, and password management URLs
2. Specify which Shibboleth headers map to user profile fields
3. Assign users into WordPress roles based on arbitrary data provided by Shibboleth

== Upgrade Notice ==

This update brings with it a major change to the way Shibboleth attributes are accessed. For most users, no additional configuration will be necessary. If you are using a specialized server configuration, such as a Shibboleth Service Provider on a reverse proxy or a server configuration that results in environment variables being sent with the prefix REDIRECT_, you should see the release notes for additional details: https://github.com/michaelryanmcneill/shibboleth/releases/tag/1.9


== Changelog ==
= version 1.8.1 (2017-09-08) =
 - Use sanitize_title rather than sanitize_user to sanitize user_nicename; props [@jrchamp](https://github.com/michaelryanmcneill/shibboleth/pull/4).
 - Changed activation and deactivation hooks to use `__FILE__`; props [@jrchamp](https://github.com/michaelryanmcneill/shibboleth/pull/5).
 - Reverted to using `$_SERVER` in `shibboleth_getenv()` to handle use cases where `getenv()` doesn't return data; [thanks to @jmdemuth for reporting](https://github.com/michaelryanmcneill/shibboleth/issues/7). 

= version 1.8 (2017-08-23) =
The Shibboleth plugin is now being maintained by [michaelryanmcneill](https://profiles.wordpress.org/michaelryanmcneill). Contributions are welcome on [GitHub](https://github.com/michaelryanmcneill/shibboleth)!

 - Adding the ability to disable .htaccess modifications with a wp-config.php constant (`SHIBBOLETH_DISALLOW_FILE_MODS`).
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
