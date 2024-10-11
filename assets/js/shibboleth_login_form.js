/**
 * Originally from Automattic's Jetpack SSO module (v5.3)
 *
 * @see https://github.com/Automattic/jetpack/blob/5.3/modules/sso/jetpack-sso-login.js.
 * @package shibboleth
 */

jQuery( document ).ready(
	function ( $ ) {
		const body = $( 'body' ),
			ssoWrap = $( '.shibboleth-wrap' ),
			loginForm = $( '#loginform' ),
			overflow = $( '<div class="shibboleth-clear"></div>' );

		// The overflow div is a poor man's clearfloat. We reposition the remember me
		// checkbox and the submit button within that to clear the float on the
		// remember me checkbox. This is important since we're positioning the SSO
		// UI under the submit button.
		//
		// @TODO: Remove this approach once core ticket 28528 is in and we have more actions in wp-login.php.
		// See - https://core.trac.wordpress.org/ticket/28528.
		loginForm.append( overflow );
		overflow.append( $( 'p.forgetmenot' ), $( 'p.submit' ) );

		// We reposition the SSO UI at the bottom of the login form which
		// fixes a tab order issue. Then we override any styles for absolute
		// positioning of the SSO UI.
		loginForm.append( ssoWrap );
		body.addClass( 'shibboleth-repositioned' );
	}
);
