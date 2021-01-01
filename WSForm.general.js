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
		$("form").one('submit', function(e) {
			//alert ( 'submitting' );
			// Check for Visial editor
			e.preventDefault();
			var pform = $(this);
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
				var VEditors = $(this).find("span.ve-area-wrapper");
				var numberofEditors = VEditors.length;
				var tAreasFieldNames = [];
				var tAreas = $(this).find("textarea").each(function(){
					tAreasFieldNames.push( $(this).attr('name') );
				});

				var veInstances = VEditors.getVEInstances();
				$(veInstances).each(function () {
					var instanceName = $(this)[0].$node[0].name;
					if( $.inArray( instanceName, tAreasFieldNames ) !== -1 ) {
						new mw.Api().post({
							action: 'veforall-parsoid-utils',
							from: 'html',
							to: 'wikitext',
							content: $(this)[0].target.getSurface().getHtml(),
							title: mw.config.get( 'wgPageName' ).split( /(\\|\/)/g ).pop()
						} )
						.then( function ( data ) {
							var text = data[ 'veforall-parsoid-utils' ].content;
							var esc = text.replace(/(?<!{{[^}]+)\|(?!=[^]+}})/gmi, "{{!}}");
							var area = pform.find("textarea[name='" + instanceName + "']")[0];
							$(area).val(esc);
							numberofEditors--;
							if( numberofEditors === 0 ){
								pform.submit();
							}
						} )
						.fail( function () {
							alert('Could not initialize ve4all, see console for error');
							console.log(result);
							pform.cancel();
						} );
					}

				});
			} else {
				pform.submit();
			}
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
