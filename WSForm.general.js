/**
 * @brief This file holds some general JavaScript for WSForm.
 * Currently it holds some function for select2 tokens (which will become deprecated)
 * and a function that will hold other JavaScript function until jQuery is loaded
 *
 * @file WSForm.general.js
 * @author Sen-Sai
 *
 */

var wsAjax = false;


/**
 * Show popup message. Initiated and loaded bu wsform function
 * @param  {[string]}  msg           [Text message]
 * @param  {[string]}  type          [what kind of alert (success, alert, warning, etc..)]
 * @param  {Boolean} [where=false] [where to show]
 * @param  {Boolean} [stick=false] [wether popup must be sticky or not]
 * @return {[type]}                []
 */
function showMessage(msg,type,where = false, stick=false) {
	if ( typeof $.notify === "undefined" ) return;
	if (where !== false) {
		if(stick) {
			where.notify(msg,type, {clickToHide: true, autoHide: false });
		} else {
			where.notify(msg,type);
		}
	} else {
		if(stick) {
			$.notify(msg, type, {clickToHide: true, autoHide: false });
		} else {
			$.notify(msg, type);
		}
	}
}

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

function waitForTinyMCE( method ) {
	if( typeof window.tinymce !== 'undefined' ){
		method();
	} else {
		setTimeout(function() { waitForTinyMCE(method) }, 50);
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

function updateVE( btn, callback, preCallback, pform ){
	// console.log("updating..");
	var VEditors = $(pform).find("span.ve-area-wrapper");
	var numberofEditors = VEditors.length;
	var tAreasFieldNames = [];
	var tAreas = $(pform).find("textarea").each(function(){
		tAreasFieldNames.push( $(this).attr('name') );
	});
	// console.log(tAreasFieldNames);
	var veInstances = VEditors.getVEInstances();
	$(veInstances).each(function () {
		var instanceName = $(this)[0].$node[0].name;
		//console.log("updating.. " + instanceName);
		if( $.inArray( instanceName, tAreasFieldNames ) !== -1 ) {
			//console.log("updating.. ..");
			new mw.Api().post({
				action: 'veforall-parsoid-utils',
				from: 'html',
				to: 'wikitext',
				content: $(this)[0].target.getSurface().getHtml(),
				title: mw.config.get( 'wgPageName' ).split( /(\\|\/)/g ).pop()
			} )
				.then( function ( data ) {
					//console.log("updating.. then");
					var text = data[ 'veforall-parsoid-utils' ].content;
					var esc = text.replace(/(?<!{{[^}]+)\|(?!=[^]+}})/gmi, "{{!}}");
					var area = pform.find("textarea[name='" + instanceName + "']")[0];
					$(area).val(esc);
					numberofEditors--;
					if( numberofEditors === 0 ) {
						WSFormEditorsUpdates = true;
						//console.log("updating.. done");
						if(window.wsAutoSaveActive === false ) {
							wsform(btn, callback, preCallback);
						} else {
							wsAutoSave(btn, true);
							window.wsAutoSaveActive = false;
						}
					}
				} )
				.fail( function () {
					//console.log("updating.. fail");
					alert('Could not initialize ve4all, see console for error');
					console.log(result);
				} );
		}

	});
}

var WSFormEditorsUpdates = false;
var wsFormTimeOutId = [];
var wsAutoSaveActive = false;

function wsAutoSave( form, reset = false ){
	var frm = $(form).closest('form');
	var type = $(frm).attr('data-autosave');
	var mwonsuccessBackup = false;
	if ( typeof window.mwonsuccess === 'undefined' ) {
		window.mwonsuccess = 'Autosave';
	} else {
		mwonsuccessBackup = window.mwonsuccess;
		window.mwonsuccess = 'Autosave';
	}
	wsAutoSaveActive = true;
	//console.log (window.wsAjax );
	if( window.wsAjax === true ) {
		$(form).click();
	} else {
		wsform( form );
	}
	if( mwonsuccessBackup !== false ) {
		window.mwonsuccess = mwonsuccessBackup;
	} else {
		delete window.mwonsuccess;
	}
	if( reset !== false ) {
		clearTimeout(wsFormTimeOutId[reset + '_general']);
		setGlobalAutoSave(form, reset);

	}
}


/**
 *
 * @param object btn
 * @param int id
 */
function setGlobalAutoSave( btn, id ) {
	var toggleBtn = $('#btn-' + id);
	wsFormTimeOutId[id + '_general'] = setTimeout(function () {
		if ($(toggleBtn).hasClass('ws-interval-on')) {
			wsAutoSave(btn, id);
		} else console.log('skipped');
	}, wsAutoSaveGlobalInterval);
}

function wsFormTinymceReady( editorid ) {
	var _editor = tinymce.editors[editorid];
	var txtare = _editor.getElement();
	var form = $(txtare).closest('form');
	_editor.on("change", function(e){
		//console.log('changed');
		_editor.save();
		wsSetEventsAutoSave( form );
	});
}

/**
 * @param object form
 */
function wsSetEventsAutoSave( form ) {
	var type = $(form).attr('data-autosave');
	var id = $(form).attr('id');
	var btn = "";
	if( window.wsAjax ) {
		$(form).find("input[type=button]").each(function(){
			if( typeof $(this).attr('onclick') !== 'undefined' && $(this).attr('onclick') !== false ) {
				btn =  this;
			}
		});
	} else {
		btn = $("input[type=submit]", form);
	}
	if( typeof wsFormTimeOutId !== 'undefined' ) {
		clearTimeout(wsFormTimeOutId[id + '_general']);
		if( wsFormTimeOutId[id] !== undefined ) {
			clearTimeout(wsFormTimeOutId[id]);
		}
	}
	if( type === 'auto' || type === 'onchange' ) {
		wsFormTimeOutId[id] = setTimeout(function () {
			wsAutoSave(btn, false);
		}, wsAutoSaveOnChangeInterval);
	}
	if( type === 'auto' || type === 'oninterval' ) {
		setGlobalAutoSave(btn, id);
	}
}

function wsToggleIntervalSave( element ) {
	var id = $(element).attr('id');
	var splitResult = id.split('-');
	var formId = splitResult[1];
	var text = $(element).text();
	if ($(element).hasClass('ws-interval-on')) {
		$(element).removeClass('ws-interval-on');
		$(element).removeClass('btn-primary');
		$(element).addClass('btn-btn');
		$(element).addClass('ws-interval-off');
		$(element).text( wsAutoSaveButtonOff );
	} else {
		$(element).removeClass('ws-interval-off');
		$(element).removeClass('btn-btn');
		$(element).addClass('btn-primary');
		$(element).addClass('ws-interval-on');
		$(element).text( wsAutoSaveButtonOn );
		if( window.wsAjax ) {
			$('#' + formId).find("input[type=button]").each(function(){
				if( typeof $(this).attr('onclick') !== 'undefined' && $(this).attr('onclick') !== false ) {
					setGlobalAutoSave(this, formId);
				}
			});
		} else {
			$('#' + formId).find("input[type=submit]").each(function () {
				setGlobalAutoSave(this, formId);
			});
		}
	}
}

function wsAutoSaveInit() {
	console.log("init autosave");
	var autosaveForms = $('form.ws-autosave');
	autosaveForms.each(function(){
		var type = $(this).attr('data-autosave');
		var form = this;
		var id = $(this).attr('id');
		if( typeof id === 'undefined' ) {
			return;
		}
		//observer[id] = new MutationObserver(function(){

		if( type === 'auto' || type === 'oninterval' ) {
			$('<button onClick="wsToggleIntervalSave(this)" class="btn btn-primary ws-interval-on" id="btn-' + id + '">Autosave is On</button>').insertBefore(form);

			$(form).find("input[type=submit]").each(function () {
				setGlobalAutoSave(this, id);
			});
		}
		if( type === 'auto' || type === 'onchange' ) {
			$(this).on('input paste change', 'input, select, textarea, div', function () {
				wsSetEventsAutoSave(form);
			});
		}

		checkForTinyMCE();
		//observer[id].observe(this, { childList: true, subtree: true } );
	});
}


/**
 * WSform Ajax handler
 * @param  {[object]}  btn              [btn that was clicked]
 * @param  {Boolean or boolean} [callback=false] [either function to callback or false if none]
 * @return {[none]}                   [run given callback]
 */
function wsform(btn,callback = 0, preCallback = 0) {

	if(preCallback !== 0 && typeof preCallback !== 'undefined') {
		preCallback(btn,callback);
		return;
	}
	$(btn).addClass( "disabled" );

	if (typeof $.notify === "undefined" ) {
		var u = mw.config.values.wgScriptPath;

		if( u === "undefined" ) {
			u = "";
		}

		$.getScript(u + '/extensions/WSForm/modules/notify.js');
	}



	//console.log(callback);
	var val = $(btn).prop('value');
	var frm = $(btn).closest('form');
	frm.addClass( "wsform-submitting" );

	if ( typeof WSFormEditor !== 'undefined' && WSFormEditor === 'VE' && WSFormEditorsUpdates === false ) {
		updateVE( btn, callback, preCallback, frm );
	} else {

		if ( typeof window.mwonsuccess === 'undefined' ) {
			var mwonsuccess = 'Saved successfully';
		} else mwonsuccess = window.mwonsuccess;
		// Added posting as user for Ajax v0.8.0.5.8
		var res = $(frm).find('input[name="wsuid"]');
		if ($(res) && $(res).length === 0) {
			var uid = getUid();
			//console.log( uid );
			if (uid !== false) {
				$('<input />')
					.attr('type', 'hidden')
					.attr('name', 'wsuid')
					.attr('value', uid)
					.appendTo(frm);
			}
		}

		if( window.wsAjax === false ) {
			var doWeHaveField = $(frm).find( 'input[name="mwidentifier"]');
			// $ret = '<input type="hidden" name="mwidentifier" value="' . $v . '">' . "\n";
			if ($(doWeHaveField) && $(doWeHaveField).length === 0) {
				$('<input />')
					.attr('type', 'hidden')
					.attr('name', 'mwidentifier')
					.attr('value', 'ajax')
					.appendTo(frm);
			}
		}

		var test = frm[0].checkValidity();
		if (test === false) {
			frm[0].reportValidity();
			$(btn).removeClass("disabled");
			frm.removeClass("wsform-submitting");
			return false;
		}

		var content = frm.contents();
		var dat = frm.serialize();
		var target = frm.attr('action');
		//form.empty();
		//form.addClass('wsform-show');

		$.ajax({
			url: target,
			type: 'POST',
			data: dat,
			dataType: "json",
			success: function (result) {
				$(btn).removeClass("disabled");
				frm.removeClass("wsform-submitting");
				frm.addClass("wsform-submitted");
				//alert(result);
				if( window.wsAjax === false ) {
					$(frm).find( 'input[name="mwidentifier"]')[0].remove();
				}
				if (result.status === 'ok') {
					showMessage( mwonsuccess, "success", $(btn));
					if (callback !== 0 && typeof callback !== 'undefined') {
						callback(frm);
					}
					//$(btn).prop('value', val + ' (saved)');
				} else {
					$.notify('WSForm : ERROR: ' + result.message, "error");
					//$(btn).prop('value', val + ' (ERROR: '+result.message+')');
				}
				//form.removeClass('wsform-show');
				//form.html(content);
			}
		});
		if ( typeof WSFormEditor !== 'undefined' && WSFormEditor === 'VE' ){
			WSFormEditorsUpdates = false;
		}
	}
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

		if( typeof window.wsAutoSaveInitAjax === 'undefined' ) {
			wachtff( wsAutoSaveInit );
		}


		//alert('adding tokeninfo');
		$("form.wsform").one('submit', function(e) {
			//alert ( 'submitting' );
			// Check for Visial editor
			e.preventDefault();
			var pform = $(this);
			if( $(this).data( 'wsform' ) && $(this).data( 'wsform' ) === 'wsform-general' ) {
				// We have a WSForm form
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

function wsInitTinyMCE(){
	for( id in window.tinymce.editors ) {
		if( id.trim() ) {
			wsFormTinymceReady( id );
		}
	}
	window.tinymce.on('AddEditor', function (e) {
		wsFormTinymceReady(e.editor.id);
	});
}

function checkForTinyMCE(){
	if ($(".tinymce")[0]){
		if( typeof window.tinymce === 'undefined' ) {
			waitForTinyMCE( wsInitTinyMCE );
		} else wsInitTinyMCE();
	}
}

/**
 * Wait for jQuery to load and initialize, then go to method addTokenInfo()
 */
wachtff( addTokenInfo );
wachtff( initializeWSFormEditor );
wachtff( checkForTinyMCE );


// tinyMCE stuff if needed



