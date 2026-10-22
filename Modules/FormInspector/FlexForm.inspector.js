/**
 * FlexForm Admin Inspector UI handlers
 */
( function () {
	'use strict';

	function generateFieldSnippet( paramName, type ) {
		var label = '<label for="' + paramName + '">' + paramName + '</label>\n';

		switch ( type ) {
			case 'textarea':
				return label + '<textarea name="' + paramName + '" id="' + paramName + '"></textarea>';
			case 'select':
				return label + '<select name="' + paramName + '" id="' + paramName + '">\n  <option value="">-- Select --</option>\n</select>';
			case 'token':
				return label + '<_token name="' + paramName + '" id="' + paramName + '"></_token>';
			case 'hidden':
				return '<input type="hidden" name="' + paramName + '" value="" />';
			default:
				return label + '<input type="' + type + '" name="' + paramName + '" id="' + paramName + '" />';
		}
	}

	document.addEventListener( 'click', function ( e ) {
		// 1. "+ Code" button clicked
		var addBtn = e.target.closest( '.ff-insp-add-code' );
		if ( addBtn ) {
			var formId = addBtn.getAttribute( 'data-form-id' );
			var paramName = addBtn.getAttribute( 'data-param' );
			var controlsWrap = addBtn.closest( '.ff-insp-gen-controls' );
			var selectEl = controlsWrap ? controlsWrap.querySelector( '.ff-insp-type-select' ) : null;
			var fieldType = selectEl ? selectEl.value : 'text';

			var snippet = generateFieldSnippet( paramName, fieldType );
			var codeWrap = document.getElementById( 'ff-codegen-wrap-' + formId );
			var codeOut = document.getElementById( 'ff-codegen-out-' + formId );

			if ( codeWrap && codeOut ) {
				codeWrap.style.display = 'block';
				codeOut.value = ( codeOut.value ? codeOut.value + '\n\n' : '' ) + snippet;
			}
			return;
		}

		// 2. "Copy to clipboard" button clicked
		var copyBtn = e.target.closest( '.ff-insp-copy-code' );
		if ( copyBtn ) {
			var copyFormId = copyBtn.getAttribute( 'data-form-id' );
			var copyOut = document.getElementById( 'ff-codegen-out-' + copyFormId );

			if ( copyOut && navigator.clipboard ) {
				var originalText = copyBtn.textContent;
				navigator.clipboard.writeText( copyOut.value ).then( function () {
					copyBtn.textContent = '✓ Copied!';
					setTimeout( function () {
						copyBtn.textContent = originalText;
					}, 1500 );
				} );
			}
			return;
		}

		// 3. "Clear" button clicked
		var clearBtn = e.target.closest( '.ff-insp-clear-code' );
		if ( clearBtn ) {
			var clearFormId = clearBtn.getAttribute( 'data-form-id' );
			var clearWrap = document.getElementById( 'ff-codegen-wrap-' + clearFormId );
			var clearOut = document.getElementById( 'ff-codegen-out-' + clearFormId );

			if ( clearOut ) {
				clearOut.value = '';
			}
			if ( clearWrap ) {
				clearWrap.style.display = 'none';
			}
		}
	} );
}() );
