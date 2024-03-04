function setupRecaptchaEnterprise() {
	$( '#%%id%%' ).submit( function ( event ) {
		event.preventDefault();

		grecaptcha.ready( function () {
			grecaptcha.enterprise.ready(async () => {
				grecaptcha.enterprise.execute( '%%reCaptchaEnterpriseID%%', { action: '%%action%%' } ).then( function ( token ) {
					$( '#%%id%%' ).prepend( '<input type="hidden" name="mw-captcha-token" value="' + token + '">' );
					$( '#%%id%%' ).prepend( '<input type="hidden" name="mw-captcha-action" value="%%action%%">' );
					$( '#%%id%%' ).prepend( '<input type="hidden" name="mw-captcha-type" value="enterprise>' );
					$( '#%%id%%' ).unbind( 'submit' ).submit();
				});
			});
		});
	});
}

wachtff( setupRecaptchaEnterprise );