/**
 * @brief This file holds some general JavaScript for WSForm.
 * Currently it holds some function for select2 tokens (which will become deprecated)
 * and a function that will hold other JavaScript function until jQuery is loaded
 *
 * @file WSForm.general.js
 * @author Sen-Sai
 *
 */


/**
 * Holds further JavaScript execution intull jQuery is loaded
 * @param method string Name of the method to call once jQuery is ready
 * @param both bool if true it will also wait until jQuery.ui is loaded.
 */
function wachtff(method, both = false) {
    //console.log('wacht ff op jQuery..');
    if (window.jQuery) {
        if (both === false) {
	        //console.log( 'ok JQuery active.. lets go!' );
	        method();
        } else {
	       // console.log('wacht ff op jQuery.ui..');
            if(window.jQuery.ui) {
	            //console.log( 'ok JQuery.ui active.. lets go!' );
	            method();
            } else {
	            setTimeout(function() { wachtff(method, true) }, 50);
            }
        }
    } else {
        setTimeout(function() { wachtff(method) }, 50);
    }
}
function waitForVE( method ) {
	if( typeof $().applyVisualEditor === 'function' ){
		method();
	} else {
		setTimeout(function() { waitForVE(method) }, 50);
	}
}

function initializeWSFormEditor(){
	if ( typeof WSFormEditor !== 'undefined' && WSFormEditor === 'VE') {
		waitForVE( initializeVE );
	}
}

function initializeVE(){
	$('.ve-area-wrapper textarea').each(function () {
		var textAreaContent = $(this).val();
		var pipesReplace = textAreaContent.replace(/{{!}}/gmi, "|");
		$(this).val(pipesReplace);
		$(this).applyVisualEditor();
		$(this).removeClass('load-editor');
	});

}


function getEditToken() {
	if (window.mw) {
		var tokens = mw.user.tokens.get();
		if ( tokens.csrfToken ) {
			return mw.user.tokens.get('editToken');
			//return tokens.csrfToken;
		}

	} else return false;
}

function getUid() {
	if (window.mw) {
		return mw.config.get('wgUserId');
	} else return false;
}

// Used for SMQ queries
function testSelect2Callback(state) {
	return state.text;
}


function addTokenInfo() {
	$(document).ready(function() {
		//alert('adding tokeninfo');
		$("form").on('submit', function() {
			//alert ( 'submitting' );
			// Check for Visial editor

			if( $(this).data( 'wsform' ) && $(this).data( 'wsform' ) === 'wsform-general' ) {
				// We have a WSForm form
				alert ( 'adding fields' );
				$('<input />')
					.attr('type', 'hidden')
					.attr('name','wsedittoken')
					.attr('value', getEditToken())
					.appendTo(this);
			}
			var res = $(this).find( 'input[name="wsuid"]');

			//console.log(res);
			if( $(res) && $(res).length === 0 ) {
				var uid = getUid();
				//console.log( uid );
				if( uid !== false ) {
					$('<input />')
						.attr('type', 'hidden')
						.attr('name', 'wsuid')
						.attr('value', uid)
						.appendTo(this);
					//alert('ok');
				}
			}
			if ( typeof WSFormEditor !== 'undefined' && WSFormEditor === 'VE') {

				$(this).find("span.ve-area-wrapper").each(function () {

					var veInstance = $(this).getVEInstances();
					var editor = veInstance[ veInstance.length - 1 ];
					//console.log(editor);
					if( editor.$node.length > 0 ) {
						editor.target.updateContent()
							.fail(function( result ) {
								alert('Could not initialize ve4all, see console for error');
								console.log(result);
								return false;
							})
							.done(function(){
								var area = $(this).find('textarea')[0];
								var areaTxt = area.val();
								var esc = areaTxt.replace(/(?<!{{[^}]+)\|(?!=[^]+}})/gmi, "{{!}}");
								area.val(esc);
								addTokenInfo(this);
								return true;
							});
					}
				});
			} else {
				return true;
			}
			return false;
		});
	});

}

function attachTokens() {
	if ($('select[data-inputtype="ws-select2"]')[0]) {
		mw.loader.load('/extensions/WSForm/select2.min.css', 'text/css');
		$.getScript('/extensions/WSForm/select2.min.js').done(function() {
			$('select[data-inputtype="ws-select2"]').each(function() {
				var selectid = $(this).attr('id');
				var selectoptionsid = 'select2options-' + selectid;
				var select2config = $("input#" + selectoptionsid).val();
				var F = new Function(select2config);
				return (F());
			});
		});
	}
}

/**
 * Wait for jQuery to load and initialize, then go to method addTokenInfo()
 */
wachtff( addTokenInfo );
wachtff( initializeWSFormEditor );
