function setupRecaptcha() {
	$( '#%%id%%' ).submit( function ( event ) {
		event.preventDefault();

		grecaptcha.ready( function () {
			grecaptcha.execute( '%%sitekey%%', {action: '%%action%%'} ).then(function (token) {
				$( '#%%id%%' ).prepend( '<input type="hidden" name="mw-captcha-token" value="' + token + '">' );
				$( '#%%id%%' ).prepend( '<input type="hidden" name="mw-captcha-action" value="%%action%%">' );
				$( '#%%id%%' ).unbind( 'submit' ).submit();
			});
		});
	});
}

ffHoldTillReady(setupRecaptcha);