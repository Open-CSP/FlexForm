if ( typeof window.ff_separator === 'undefined' ) {
	window.ff_separator = ',';
}

/**
 * FlexForm Tempex function
 */
window.ffTempex = ( element = null, isPredefined = false ) => {
	if ( element === null ) {
		$( 'form.flex-form' ).each( ( i, form ) => {
			ffTempex( form );
		} );
		return;
	}

	if ( $( element ).is( 'form' ) && $( element ).find( '.WSmultipleTemplateWrapper' ).length > 0 ) {
		return;
	}

	/**
	 * Helper to find inputs by name OR array-name (e.g. "field" or "field[]")
	 * @param $context {jQuery}
	 * @param name {string}
	 * @returns {jQuery}
	 */
	const findInputByName = ( $context, name ) => {
		// FlexForm (and HTML in general) uses name[] for checkboxes/selects to handle arrays.
		// We select both strict name and name+[] to be safe.
		return $context.find( `[name="${name}"], [name="${name}[]"]` );
	};

	/**
	 * Helper function to get the value based on input type
	 * Handles Checkboxes, Radio buttons and Selects specifically
	 * @param $context {jQuery} The form element (context)
	 * @param name {string} The name attribute of the field
	 * @return {string}
	 */
	const getFieldValue = ( $context, name ) => {
		const $input = findInputByName( $context, name );

		if ( $input.length === 0 ) {
			return '';
		}

		// Handle Radio buttons (only get the checked one)
		if ( $input.is( ':radio' ) ) {
			return $input.filter( ':checked' ).val() || '';
		}

		// Handle Checkboxes (handle single and grouped checkboxes)
		if ( $input.is( ':checkbox' ) ) {
			let checked = $input.filter( ':checked' );
			if ( checked.length === 0 ) {
				return '';
			}
			// Map values to array and join with comma
			return checked.map( function() { return this.value; } ).get().join( window.ff_separator );
		}

		// Handle Select (Multi-select returns array, join it)
		if ( $input.is( 'select' ) ) {
			let val = $input.val();
			return Array.isArray( val ) ? val.join( window.ff_separator ) : ( val || '' );
		}

		// Default for Text, Number, Textarea, Hidden, etc.
		return $input.val() || '';
	};

	/**
	 * Returns the names of the input field used for the template call
	 * @param txt {string}
	 * @return []
	 */
	const extractNamesFromDataset = ( txt ) => {
		return Array.from( txt.split( '|' ) ).slice( 1 );
	};

	/**
	 * Template extract function
	 * @param field {HTMLElement}
	 */
	const tempex = ( field ) => {
		let templateCall = $( field ).data( 'tempex' );
		// decrypt if necessary (assuming getDecrypt is globally available)
		if ( typeof getDecrypt === 'function' ) {
			templateCall = getDecrypt( templateCall );
		}

		const names = extractNamesFromDataset( templateCall );
		let name_value_obj = {};

		names.forEach( n => {
			name_value_obj[n] = getFieldValue( $( element ), n );

			if ( !name_value_obj[n] ) {
				name_value_obj[n] = '';
			}

			// update template call
			templateCall = templateCall.replaceAll( `|${n}`, `|${n}=${name_value_obj[n]}` );
		} );

		// Basic check to prevent empty API calls if needed, logic preserved from original
		if ( Object.keys( name_value_obj ).length === 1 && Object.values( name_value_obj )[0] === '' )  {
			return;
		}

		// Parse the template
		new mw.Api().parse( `{{${templateCall}}}` )
			.done( function ( data ) {
				if ( isPredefined ) {
					return;
				}

				let resultText = $( data ).find( 'p' ).text().trim();

				if ( field.type === 'number' ) {
					$( field ).val( +resultText );
				} else {
					$( field ).val( resultText );
				}
			} );

		isPredefined = false;
	};

	// Find the tempex fields present in the forms
	// Let's target the attribute present in DOM.
	const tempexFields = $( element ).find( '[tempex], [data-tempex]' );

	if ( tempexFields.length > 0 ) {
		tempexFields.each( function ( i, field ) {
			// Normalize retrieving the data string
			let rawData = $( field ).attr('tempex') || $( field ).attr('data-tempex');
			// If encrypted/handled by FlexForm JS previously, it might be in .data() already
			// We use the raw attribute to get the names initially.

			// NOTE: The original code used .data('tempex'). Ensure this works with your FlexForm version.
			let templateCall = $( field ).data( 'tempex' ) || rawData;

			if ( typeof getDecrypt === 'function' ) {
				templateCall = getDecrypt( templateCall );
			}

			const names = extractNamesFromDataset( templateCall );

			let everyInputIsFound = true;
			names.forEach( n => {
				if ( findInputByName( $( element ), n ).length === 0 ) {
					everyInputIsFound = false;
				}
			} );
			if ( !everyInputIsFound ) {
				return;
			}

			// Add custom event listener
			$( field ).on( 'fftempex', function ( e ) {
				e.stopImmediatePropagation();
				e.preventDefault();
				tempex( field );
			} );

			// Loop through names and attach 'change' listener to the actual inputs
			names.forEach( n => {
				// Use the helper to find correct inputs (including brackets)
				findInputByName( $( element ), n ).on( 'change', function ( e ) {
					$( field ).trigger( 'fftempex', e );
				} );
			} );
		} );
	}
}