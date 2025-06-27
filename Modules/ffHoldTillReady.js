function isFFInitiated() {
	return typeof window.FlexFormInitiated !== 'undefined';
}

function FFInitiated() {
	if ( typeof window.FlexFormInitiated === 'undefined' ) {
		window.FlexFormInitiated = true;
		console.log( "FF Initialized" );
	}
}

/**
 * Holds further JavaScript execution until jQuery is loaded
 * @param method string Name of the method to call once jQuery is ready
 * @param both bool if true it will also wait until MW is loaded.
 */
function ffHoldTillReady( method, both= true ) {
		if ( window.jQuery ) {
		if ( both === false ) {
			if ( window.wsform ) {
				method();
			} else {
				setTimeout( function () {
					ffHoldTillReady( method, true )
				}, 250 )
			}
		} else {
			if ( window.mw ) {
				var scriptPath = mw.config.get( 'wgScript' )
				if ( scriptPath !== null && scriptPath !== false ) {
					scriptPath = scriptPath.replace( '/index.php', '' );
					if ( window.wsform ) {
						method();
					} else {
						if ( isFFInitiated() === false ) {
							FFInitiated();
							$.getScript(scriptPath + '/extensions/FlexForm/Modules/FlexForm.general.js').done(function () {
								method()
							});
						} else {
								setTimeout( function () {
								ffHoldTillReady( method, true )
							}, 250 )
						}
					}
				} else {
					setTimeout( function () {
						ffHoldTillReady( method, true )
					}, 250 )
				}
			} else {
				setTimeout( function () {
					ffHoldTillReady( method, true )
				}, 250 )
			}
		}
	} else {
		setTimeout( function () {
			ffHoldTillReady( method, both )
		}, 50 )
	}
}


function attachTokens () {
	$(document).ready(function () {
		if ($('select[data-inputtype="ws-select2"]')[0]) {
			var scriptPath = mw.config.get('wgScript')
			if (scriptPath === null || !scriptPath) {
				scriptPath = ''
			}
			scriptPath = scriptPath.replace('/index.php', '')
			mw.loader.load(scriptPath + '/extensions/FlexForm/Modules/select2.min.css', 'text/css')
			$.getScript(scriptPath + '/extensions/FlexForm/Modules/select2.min.js').done(function () {
				$( 'select[data-inputtype="ws-select2"]' ).each( function () {
					var selectid = $( this ).attr( 'id' )
					var selectoptionsid = 'select2options-' + selectid
					var select2config = $( 'input#' + selectoptionsid ).val()
					var F = new Function( select2config )
					return ( F() )
				} )

			})
		}
	})
}

function initiateFirstRun() {

}

ffHoldTillReady( initiateFirstRun );
