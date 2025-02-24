/**
 * applying show on select on the page and make sure everything will be handled as needed
 */
async function WsShowOnSelect(selector = document.body) {
	await waitForJQueryIsReady();
	let divWait = document.querySelector('form')
  $('form.WSShowOnSelect').each( function(){
    if( $( this ).hasClass( 'flex-form-hide' ) ) {
      showWeAreWorking( this );
    }
  });


	let selectArray = []
	$(selector).find('.WSShowOnSelect').find('[data-wssos-show]').each(function (index, elm) {
		if ($(elm).is('option')) {
			let isInArray = false
			let selectParent = $(elm).parent()[0];
			if ( $(selectParent).is('optgroup') ) {
				selectParent = $(selectParent).parent()[0];
			}
			for (let i = 0; i < selectArray.length; i++) {
				if ($(selectParent).is($(selectArray[i]))) {
					isInArray = true
				}
			}
			if (!isInArray) {
				selectArray.push(selectParent)
				handleSelect(selectParent)
			}
		} else if ($(elm).is('input[type=radio]')) {
			handleRadio(elm)
		} else if ($(elm).is('input[type=checkbox]')) {
			handleCheckbox(elm)
		} else if ($(elm).is('button')) {
			handleButton(elm)
		} else if ($(elm).is('input') || $(elm).is('textarea')) {
			handleInput(elm);
		}
	})
	$('form.WSShowOnSelect').each( function(){
		if( $( this ).hasClass( 'flex-form-hide' ) ) {
			weAreDoneWorking( this );
		}
	});
	$('.flex-form-hide').removeClass('flex-form-hide');

}





/**
 * wait function for jQuery
 * @returns {Promise<void>}
 */
async function waitForJQueryIsReady () {
	/**
	 * sleep function
	 * @param ms
	 * @returns {Promise<unknown>}
	 */
	function sleep (ms) {
		return new Promise(resolve => setTimeout(resolve, ms))
	}

	// counter for nr loops
	let counter = 0

	/**
	 * checks if jQuery is ready
	 * @returns {Promise<void>}
	 */
	async function checkForJQuery () {
		if (!$.isReady && counter < 20) {
			await sleep(200)
			await checkForJQuery()
			counter++
		}
	}

	await checkForJQuery()
}

/**
 * handle the radio button changes, show what is needed
 * @param radioElm
 */
function handleRadio (radioElm) {
	let wssos_value = $(radioElm).data('wssos-show')
	let parent_wssos = $(radioElm).parentsUntil('.WSShowOnSelect').parent()[0]
	let wssos_elm = $(parent_wssos).find('[data-wssos-value*="' + wssos_value + '"]')

	if ($(radioElm).parent().hasClass('WSShowOnSelect')) {
		parent_wssos = $(radioElm).parent()[0]
		wssos_elm = $(parent_wssos).find('[data-wssos-value*="' + wssos_value + '"]')
	}

	// if no elements are found, first look up id
	if (wssos_elm.length === 0) wssos_elm = $(parent_wssos).find('#' + wssos_value)

	let radio = radioElm;

	/**
	 * Callback for the loop function
	 * @param index {number}
	 * @param element {HTMLInputElement}
	 */
	const radioElementCb = (index, element) => {
		element = $(element);
		let needToShow = false;
		let element_wssos_value = element.data('wssos-value');

		// check if multiple tags are separated by same separator
		if ( !checkIfMultipleTagsSeparatedBySame(element_wssos_value) ) return;

		// are there multiple tags that need to be handled as OR
		if (element_wssos_value.split('||').length > 1) {
			needToShow = handleMultipleTags(element_wssos_value.split('||'), parent_wssos, false, element);
		}
		// are there multiple tags that need to be handled as AND
		else if (element_wssos_value.split('&&').length > 1) { // AND
			needToShow = handleMultipleTags(element_wssos_value.split('&&'), parent_wssos, true, element);
		}
		// there are no multiple tags
		else {
			// check if it is exactly that value
			if (element_wssos_value !== wssos_value) return;
			needToShow = radio.checked;
		}

		if (needToShow) {
			element.show(0);
			putAllTypesDataInName(element);
		} else {
			element.hide(0);
			putAllTypesNameInData(element);
		}
	}

	$.each(wssos_elm, (index, element) => radioElementCb(index, element));

	$(radioElm).off('change')
	$(radioElm).on('change', function () {
		wssos_value = $(this).data('wssos-show')
		parent_wssos = $(this).parentsUntil('.WSShowOnSelect').parent()[0]
		wssos_elm = $(parent_wssos).find('[data-wssos-value*="' + wssos_value + '"]')

		if ($(this).parent().hasClass('WSShowOnSelect')) {
			parent_wssos = $(this).parent()[0]
			wssos_elm = $(parent_wssos).find('[data-wssos-value*="' + wssos_value + '"]')
		}

		// if no elements are found, first look up id, then elements that include the tag
		if (wssos_elm.length === 0) wssos_elm = $(parent_wssos).find('#' + wssos_value)

		// loop through the radio button groep and hide others
		$(parent_wssos).find('input[name="' + this.name + '"][type="radio"]').each(function (index, radiobtn) {
			let radio_hide_data_attr = $(radiobtn).data('wssos-show')
			let radio_hide_elm = $(parent_wssos).find('[data-wssos-value*="' + radio_hide_data_attr + '"]')

			if (radio_hide_elm.length === 0) radio_hide_elm = $(parent_wssos).find('#' + radio_hide_data_attr)
			if (radio_hide_elm.length === 0) radio_hide_elm = $(parent_wssos).find('[data-wssos-value*="' + radio_hide_data_attr + '"]')

			$.each(radio_hide_elm, (index, element) => {
				element = $(element);
				let needToShow = false;
				let element_wssos_value = element.data('wssos-value');

				// check if multiple tags are separated by same separator
				if ( !checkIfMultipleTagsSeparatedBySame(element_wssos_value) ) return;

				if (element_wssos_value.split('||').length > 1) {
					if (!element_wssos_value.split('||').includes(radio_hide_data_attr)) return;
					needToShow = handleMultipleTags(element_wssos_value.split('||'), parent_wssos, false, element);
				}

				if (element_wssos_value.split('&&').length > 1) {
					if (!element_wssos_value.split('&&').includes(radio_hide_data_attr)) return;
					needToShow = handleMultipleTags(element_wssos_value.split('&&'), parent_wssos, true, element);
				}


				if (needToShow) {
					element.show(0);
					putAllTypesDataInName(element);
				} else {
					element.hide(0);
					putAllTypesNameInData(element);
				}
			});
		})

		radio = this;
		$.each(wssos_elm, (index, element) => radioElementCb(index, element));
	})
}

/**
 * handle the checkbox changes, show what is needed
 * @param checkElm
 */
function handleCheckbox (checkElm) {
	let wssos_value = $(checkElm).data('wssos-show')
	let parent_wssos = $(checkElm).parentsUntil('.WSShowOnSelect').parent()[0]
	let wssos_elm = $(parent_wssos).find('[data-wssos-value*="' + wssos_value + '"]')

	if ($(checkElm).parent().hasClass('WSShowOnSelect')) {
		parent_wssos = $(checkElm).parent()[0]
		wssos_elm = $(parent_wssos).find('[data-wssos-value="' + wssos_value + '"]')
	}

	// if no elements are found, first look up id, then elements that include the tag
	if (wssos_elm.length === 0) wssos_elm = $(parent_wssos).find('#' + wssos_value)
	if (wssos_elm.length === 0) wssos_elm = $(parent_wssos).find('[data-wssos-value*="' + wssos_value + '"]')


	let check = checkElm;

	/**
	 * check elements cb
	 * @param index {number}
	 * @param element {HTMLInputElement}
	 */
	const checkElementCb = (index, element) => {
		element = $(element);
		let needToShow = false;
		let element_wssos_value = element.data('wssos-value');

		// check if multiple tags are separated by same separator
		if ( !checkIfMultipleTagsSeparatedBySame(element_wssos_value) ) return;

		// are there multiple tags that need to be handled as OR
		if (element_wssos_value.split('||').length > 1) {
			needToShow = handleMultipleTags(element_wssos_value.split('||'), parent_wssos, false, element);
		}
		// are there multiple tags that need to be handled as AND
		else if (element_wssos_value.split('&&').length > 1) {
			needToShow = handleMultipleTags(element_wssos_value.split('&&'), parent_wssos, true, element);
		}
		// there are no multiple tags
		else {
			if (element_wssos_value !== wssos_value) return;
			needToShow = check.checked;
		}

		if (needToShow) {
			element.show(0);
			putAllTypesDataInName(element);
		} else {
			element.hide(0);
			putAllTypesNameInData(element);
		}

	};

	$.each(wssos_elm, (index, element) => checkElementCb(index, element));

	// look for unchecked data attributes and handle them
	if ($(checkElm).has('data-wssos-show-unchecked')) {
		let pre_unchecked_value = $(checkElm).data('wssos-show-unchecked')
		let pre_unchecked_elm = $(parent_wssos).find('[data-wssos-value="' + pre_unchecked_value + '"]')

		if (pre_unchecked_value && pre_unchecked_value !== '' && pre_unchecked_value !== ' ') {
			if (pre_unchecked_elm.length === 0) pre_unchecked_elm = $(parent_wssos).find('#' + pre_unchecked_value)
			if (pre_unchecked_elm.length === 0) pre_unchecked_elm = $(parent_wssos).find('[data-wssos-value=*"' + pre_unchecked_value + '"]')

			if (!checkElm.checked) {
				$(pre_unchecked_elm).show(0)
				// set the name attribute to the value of data-name-attribute
				putAllTypesDataInName(pre_unchecked_elm)
			} else {
				$(pre_unchecked_elm).hide(0);
				putAllTypesNameInData(pre_unchecked_elm);
			}

		}
	}

	$(checkElm).off('change')
	$(checkElm).on('change', function (e) {
		e.stopPropagation()
		wssos_value = $(this).data('wssos-show')
		parent_wssos = $(this).parentsUntil('.WSShowOnSelect').parent()[0]
		wssos_elm = $(parent_wssos).find('[data-wssos-value*="' + wssos_value + '"]')

		if ($(this).parent().hasClass('WSShowOnSelect')) {
			parent_wssos = $(this).parent()[0]
			wssos_elm = $(parent_wssos).find('[data-wssos-value*="' + wssos_value + '"]')
		}

		if (wssos_elm.length === 0) wssos_elm = $(parent_wssos).find('#' + wssos_value)
		if (wssos_elm.length === 0) wssos_elm = $(parent_wssos).find('[data-wssos-value*="' + wssos_value + '"]')


		check = this;

		$.each(wssos_elm, (index, element) => checkElementCb(index, element));

		if ($(this).has('data-wssos-show-unchecked')) {
			let wssos_unchecked_value = $(this).data('wssos-show-unchecked')
			let wssos_unchecked_elm = $(parent_wssos).find('[data-wssos-value="' + wssos_unchecked_value + '"]')

			if (wssos_unchecked_elm.length === 0) wssos_unchecked_elm = $(parent_wssos).find('#' + wssos_unchecked_value)
			if (wssos_unchecked_elm.length === 0) wssos_unchecked_elm = $(parent_wssos).find('[data-wssos-value*="' + wssos_unchecked_value + '"]')

			if (this.checked) {
				wssos_unchecked_elm.hide(0)
				putAllTypesNameInData(wssos_unchecked_elm)
			} else {
				wssos_unchecked_elm.show(0)
				putAllTypesDataInName(wssos_unchecked_elm)
			}
		}
	})
}

/**
 * handle the select box changes to show what is needed on select
 * @param selectElm
 */
function handleSelect (selectElm) {
	let selectVal = '',
		optionElm = '',
		wssos_value = '',
		parent_wssos = '',
		wssos_elm = '',
		wssos_show_elm = null;

	/**
	 * option element cb
	 * @param index {number}
	 * @param element {HTMLInputElement}
	 */
	const optionElementCb = (index, element) => {
		element = $(element);
		let needToShow = false;
		let element_wssos_value = element.data('wssos-value');

		// check if multiple tags are separated by same separator
		if ( !checkIfMultipleTagsSeparatedBySame(element_wssos_value) ) return;

		// are there multiple tags that need to be handled as OR
		if (element_wssos_value.split('||').length > 1) {
			needToShow = handleMultipleTags(element_wssos_value.split('||'), parent_wssos, false, element);
		}

		// are there multiple tags that need to be handled as AND
		else if (element_wssos_value.split('&&').length > 1) { // AND
			needToShow = handleMultipleTags(element_wssos_value.split('&&'), parent_wssos, true, element);
		}

		// there are no multiple tags
		else {
			if (element_wssos_value !== wssos_value) return;
			needToShow = (optionElm.selected || $(optionElm).val() === selectVal);
		}

		if (needToShow) {
			element.show(0);
			putAllTypesDataInName(element);
			wssos_show_elm = element;
		} else {
			element.hide(0);
			putAllTypesNameInData(element);
		}
	}

	// loop through all options
	$(selectElm).children().each(function (index, option) {
		wssos_value = $(option).data('wssos-show')
		parent_wssos = $(option).parentsUntil('.WSShowOnSelect').parent()[0]
		wssos_elm = $(parent_wssos).find('[data-wssos-value*="' + wssos_value + '"]')

		// if no elements are found, first look for id else look for elements that include the tag
		if (wssos_elm.length === 0) wssos_elm = $(parent_wssos).find('#' + wssos_value)

		optionElm = option;
		selectVal = $(option).parent().val()

		$.each(wssos_elm, (index, element, parent_wssos ) => optionElementCb( index, element ) );
	});

	$(selectElm).off('change')
	$(selectElm).on('change', function () {
		wssos_show_elm = null

		// loop through all options
		$(this).find('[type=option]').each(function (index, option) {
			wssos_value = $(option).data('wssos-show')
			parent_wssos = $(this).parentsUntil('.WSShowOnSelect').parent()[0]
			wssos_elm = $(parent_wssos).find('[data-wssos-value*="' + wssos_value + '"]')

			// if no elements are found, first look for id else look for elements that include tag
			if (wssos_elm.length === 0) wssos_elm = $(parent_wssos).find('#' + wssos_value)

			optionElm = option;
			selectVal = $(selectElm).val();

			$.each(wssos_elm, (index, element) => optionElementCb(index, element));
		})

		if (wssos_show_elm === null) return

		wssos_show_elm.show(0)
		putAllTypesDataInName(wssos_show_elm)
	})
}

function handleButton (btnElm) {
	let pre_wssos_value = $(this).data('wssos-show')
	let pre_parent_wssos = $(this).parentsUntil('.WSShowOnSelect').parent()[0]
	let pre_wssos_elm = $(pre_parent_wssos).find('[data-wssos-value="' + pre_wssos_value + '"]')

	if (pre_wssos_elm.length === 0) pre_wssos_elm = $(pre_parent_wssos).find('#' + pre_wssos_value)

	// set up the start and make sure the element is hidden
	$(pre_wssos_elm).hide(0)
	putAllTypesNameInData(pre_wssos_elm)

	$(btnElm).off('click')
	// add on click listener to the button
	$(btnElm).on('click', function (e) {
		let wssos_value = $(this).data('wssos-show')
		let parent_wssos = $(this).parentsUntil('.WSShowOnSelect').parent()[0]
		let wssos_elm = $(parent_wssos).find('[data-wssos-value="' + wssos_value + '"]')

		if (wssos_elm.length === 0) wssos_elm = $(parent_wssos).find('#' + wssos_value)

		// possibility to hide the wanted element back if an option
		if ($(wssos_elm).css('display') !== 'none') {
			$(wssos_elm).hide(0)
			putAllTypesNameInData(wssos_elm)
		} else {
			$(wssos_elm).show(0)
			putAllTypesDataInName(wssos_elm)
		}
	})
}

/**
 * Handles the input
 * @param input {HTMLInputElement}
 */
function handleInput(input) {
	let wssos_value = $(input).data('wssos-show');
	let parent = $(input).parentsUntil('.WSShowOnSelect').parent()[0];
	let elements = $(parent).find('[data-wssos-value*="' + wssos_value + '"]');

	if ($(input).parent().hasClass('WSShowOnSelect')) {
		parent = $(input).parent()[0];
		elements = $(parent).find('[data-wssos-value*="' + wssos_value + '"]');
	}

	if (elements.length === 0) elements = $(parent).find('#' + wssos_value);

	if (!elements) return;
	if (!$(elements).data('wssos-type')) return;


	/**
	 * Handles the different show on select types, CONTAINS or EQUALS
	 * @param t {string[]} type array
	 * @param val {string}
	 * @returns {boolean|*}
	 */
	const handleTypes = (t, val) => {
		switch (t[0]) {
			case 'contains':
				return val.includes(t[1]);
			case 'equals':
				return val === t[1];
			default:
				return false;
		}
	}

	let inputElm = input;

	/**
	 * input element cb
	 * @param index
	 * @param element
	 */
	const inputElementCb = (index, element) => {
		element = $(element);
		let needToShow = false;
		let type = $(element).data('wssos-type').split('::');
		let element_wssos_value = $(element).data('wssos-value');

		// check if multiple tags are separated by same separator
		if ( !checkIfMultipleTagsSeparatedBySame(element_wssos_value) ) return;

		// are there multiple tags that need to be handled as OR
		if (element_wssos_value.split('||').length > 1) {
			needToShow = handleMultipleTags(element_wssos_value.split('||'), parent, false, element);
		}
		// are there multiple tags that need to be handled as AND
		else if (element_wssos_value.split('&&').length > 1) { // AND
			needToShow = handleMultipleTags(element_wssos_value.split('&&'), parent, true, element);
		}
		// There are no multiple tags
		else {
			if (element_wssos_value !== wssos_value) return;
			needToShow = handleTypes(type, inputElm.value);
		}

		if (needToShow) {
			element.show(0);
			putAllTypesDataInName(element);
		} else {
			element.hide(0);
			putAllTypesNameInData(element);
		}
	}

	$.each(elements, (index, element) => inputElementCb(index, element));


	$(input).off('input');
	$(input).on('input', function(e) {
		wssos_value = $(this).data('wssos-show');
		parent = $(this).parentsUntil('.WSShowOnSelect').parent()[0];
		elements = $(parent).find('[data-wssos-value*="' + wssos_value + '"]');

		if ($(this).parent().hasClass('WSShowOnSelect')) {
			parent = $(this).parent()[0];
			elements = $(parent).find('[data-wssos-value*="' + wssos_value + '"]');
		}

		if (elements.length === 0) elements = $(parent).find('#' + wssos_value);

		inputElm = this;

		$.each(elements, (index, element) => inputElementCb(index, element));
	});
}

/**
 * handle multiple tags
 * @param tags {array}
 * @param parent {HTMLElement}
 * @param isAnd {boolean}
 * @param element {HTMLElement}
 * @returns {boolean}
 */
function handleMultipleTags(tags, parent, isAnd, element) {
	let boolArray = [];
	$.each(tags, (index, tag) => {
		let input = $(parent).find('[data-wssos-show="' + tag + '"]');
		if ( input.is('input[type=checkbox]') || input.is('input[type=radio]')) {
			boolArray.push(input[0].checked);
		} else if (input.is('option')) {
			let sel = input.parent();
			boolArray.push((input[0].selected || sel.value === input[0].value));
		} else {
			let value = input[0].value;
			let type = $(element).data('wssos-type').split('::');
			if (type[0] === 'contains') {
				boolArray.push(value.includes(type[1]));
			} else if (type[0] === 'equals') {
				boolArray.push(value === type[1]);
			}
		}
	});

	if (isAnd) {
		return !boolArray.includes(false);
	}
	return boolArray.includes(true);
}

/**
 * Checks if the value that will be used to extract the tags are separated by the same condition
 * So all `&&` or all `||` not both in the same value
 * @param value {string}
 * @returns {boolean}
 */
function checkIfMultipleTagsSeparatedBySame(value) {
	let check = true;
	if ( value.split('||').length > 1 ) {
		Array.from(value.split('||')).forEach(v => {
			if ( v.split('&&').length > 1 ) {
				check = false;
			}
		});
	}
	if ( !check ) mw.notify('You cannot have different separators in the show on select value\n && and || must be used separate', {type: 'error'});
	return check;
}

/**
 * find all different types which name attribute should go to the dataset
 * @param elm
 */
function putAllTypesNameInData (elm) {
	if ($(elm).is('input,select,textarea')) {
		putNameAttrValueInDataset(elm)
		putRequiredInDataset(elm)
		return
	}
	putNameAttrValueInDataset($(elm).find('input,select,textarea'))
	putRequiredInDataset($(elm).find('input,select,textarea'))
}

/**
 * find all different types which data-attribute should go to the name-attribute
 * @param elm
 */
function putAllTypesDataInName (elm) {
	if ($(elm).is('input,select,textarea')) {
		putDatasetValueBackInName(elm)
		putDatasetInRequired(elm)
		return
	}
	putDatasetValueBackInName($(elm).find('input,select,textarea'))
	putDatasetInRequired($(elm).find('input,select,textarea'))
}

/**
 * set the name attribute value to the dataset data-name-attribute, remove the name attribute
 * @param $elm
 */
function putNameAttrValueInDataset ($elm) {
	$.each($elm, function (index, elm) {
		if ($(elm).attr('name') !== '') {
			let name = $(elm).attr('name')
			if (name) {
				$(elm).attr('data-name-attribute', name)
				$(elm).removeAttr('name')
			}
		}
	})
}

/**
 * set the name attribute to the value of the data-name-attribute
 * @param $elm
 */
function putDatasetValueBackInName ($elm) {
	$.each($elm, function (index, elm) {
		if ($(elm).attr('data-name-attribute') !== '') {
			let datasetName = $(elm).data('name-attribute')
			if (datasetName) {
				$(elm).attr('name', datasetName)
			}
		}
	})
}

/**
 * set the required attr in the dataset data-ws-required
 * @param $elm
 */
function putRequiredInDataset ($elm) {
	$.each($elm, function (index, elm) {
		if ($(elm).is(':required')) {
			$(elm).attr('data-ws-required', true)
			$(elm).prop('required', false)
		}
	})
}

/**
 * if the element has data-ws-required the make the element required
 * @param $elm
 */
function putDatasetInRequired ($elm) {
	$.each($elm, function (index, elm) {
		if ($(elm).data('ws-required')) {
			$(elm).prop('required', true)
		}
	})
}


