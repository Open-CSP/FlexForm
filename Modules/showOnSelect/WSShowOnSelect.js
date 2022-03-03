/**
 * applying show on select on the page and make sure everyting will be handled as needed
 */
async function WsShowOnSelect () {
	await waitForJQueryIsReady()
	var divWait = document.querySelector('form')
  $('form.WSShowOnSelect').each( function(){
    if( $( this ).hasClass( 'flex-form-hide' ) ) {
      showWeAreWorking( this );
    }
  });


	var selectArray = []
	$('.WSShowOnSelect').find('[data-wssos-show]').each(function (index, elm) {
		if ($(elm).is('option')) {
			var isInArray = false
			var selectParent = $(elm).parent()[0]
			for (var i = 0; i < selectArray.length; i++) {
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
	var counter = 0

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
	var pre_wssos_value = $(radioElm).data('wssos-show')
	var pre_parent_wssos = $(radioElm).parentsUntil('.WSShowOnSelect').parent()[0]
	var pre_wssos_elm = $(pre_parent_wssos).find('[data-wssos-value="' + pre_wssos_value + '"]')

	if ($(radioElm).parent().hasClass('WSShowOnSelect')) {
		pre_parent_wssos = $(radioElm).parent()[0]
		pre_wssos_elm = $(pre_parent_wssos).find('[data-wssos-value="' + pre_wssos_value + '"]')
	}

	if (pre_wssos_elm.length === 0) {
		pre_wssos_elm = $(pre_parent_wssos).find('#' + pre_wssos_value)
	}

	if (radioElm.checked) {
		$(pre_wssos_elm).show(0)
		putAllTypesDataInName(pre_wssos_elm)
	} else {
		$(pre_wssos_elm).hide(0)
		putAllTypesNameInData(pre_wssos_elm)
	}
	$(radioElm).off('change')
	$(radioElm).on('change', function () {
		var wssos_value = $(this).data('wssos-show')
		var parent_wssos = $(this).parentsUntil('.WSShowOnSelect').parent()[0]
		var wssos_elm = $(parent_wssos).find('[data-wssos-value="' + wssos_value + '"]')

		if ($(this).parent().hasClass('WSShowOnSelect')) {
			parent_wssos = $(this).parent()[0]
			wssos_elm = $(parent_wssos).find('[data-wssos-value="' + wssos_value + '"]')
		}

		if (wssos_elm.length === 0) {
			wssos_elm = $(parent_wssos).find('#' + wssos_value)
		}

		$(parent_wssos).find('input[name="' + this.name + '"][type="radio"]').each(function (index, radiobtn) {
			var radio_hide_data_attr = $(radiobtn).data('wssos-show')
			var radio_hide_elm = $(parent_wssos).find('[data-wssos-value="' + radio_hide_data_attr + '"]')

			if (radio_hide_elm.length === 0) radio_hide_elm = $(parent_wssos).find('#' + radio_hide_data_attr)

			radio_hide_elm.hide(0)
			putAllTypesNameInData(radio_hide_elm)
		})

		if (this.checked) {
			wssos_elm.show(0)
			putAllTypesDataInName(wssos_elm)
		} else {
			wssos_elm.hide(0)
			putAllTypesNameInData(wssos_elm)
		}
	})
}

/**
 * handle the checkbox changes, show what is needed
 * @param checkElm
 */
function handleCheckbox (checkElm) {
	var pre_wssos_value = $(checkElm).data('wssos-show')
	var pre_parent_wssos = $(checkElm).parentsUntil('.WSShowOnSelect').parent()[0]
	var pre_wssos_elm = $(pre_parent_wssos).find('[data-wssos-value="' + pre_wssos_value + '"]')

	if ($(checkElm).parent().hasClass('WSShowOnSelect')) {
		pre_parent_wssos = $(checkElm).parent()[0]
		pre_wssos_elm = $(pre_parent_wssos).find('[data-wssos-value="' + pre_wssos_value + '"]')
	}

	if (pre_wssos_elm.length === 0) {
		pre_wssos_elm = $(pre_parent_wssos).find('#' + pre_wssos_value)
	}

	if (checkElm.checked) {
		pre_wssos_elm.show(0)
		// set the dataset value of data-name-attribute back in the name attribute
		putAllTypesDataInName(pre_wssos_elm)

		// set the name value of the unchecked element in the value of data-name-attribute and remove the name attribute
		if ($(checkElm).has('data-wssos-show-unchecked')) {
			var pre_unchecked_value = $(checkElm).data('wssos-show-unchecked')
			var pre_unchecked_elm = $(pre_parent_wssos).find('[data-wssos-value="' + pre_unchecked_value + '"]')

			if (pre_unchecked_elm.length === 0) pre_unchecked_elm = $(pre_parent_wssos).find('#' + pre_unchecked_value)

			putAllTypesNameInData(pre_unchecked_elm)
		}
	} else {
		pre_wssos_elm.hide(0)
		// set data-name-attribute to the value of name attribute and remove the name attribute
		putAllTypesNameInData(pre_wssos_elm)

		if ($(checkElm).has('data-wssos-show-unchecked')) {
			var pre_unchecked_value = $(checkElm).data('wssos-show-unchecked')
			var pre_unchecked_elm = $(pre_parent_wssos).find('[data-wssos-value="' + pre_unchecked_value + '"]')

			if (pre_unchecked_value && pre_unchecked_value !== '' && pre_unchecked_value !== ' ') {
				if (pre_unchecked_elm.length === 0) pre_unchecked_elm = $(pre_parent_wssos).find('#' + pre_unchecked_value)

				$(pre_unchecked_elm).show(0)
				// set the name attribute to the value of data-name-attribute
				putAllTypesDataInName(pre_unchecked_elm)
			}
		}
	}

	$(checkElm).off('change')
	$(checkElm).on('change', function (e) {
		e.stopPropagation()
		var wssos_value = $(this).data('wssos-show')
		var parent_wssos = $(this).parentsUntil('.WSShowOnSelect').parent()[0]
		var wssos_elm = $(parent_wssos).find('[data-wssos-value="' + wssos_value + '"]')

		if ($(this).parent().hasClass('WSShowOnSelect')) {
			parent_wssos = $(this).parent()[0]
			wssos_elm = $(parent_wssos).find('[data-wssos-value="' + wssos_value + '"]')
		}

		if (wssos_elm.length === 0) wssos_elm = $(parent_wssos).find('#' + wssos_value)

		if (this.checked) {
			wssos_elm.show(0)
			putAllTypesDataInName(wssos_elm)
		} else {
			wssos_elm.hide(0)
			putAllTypesNameInData(wssos_elm)
		}

		if ($(this).has('data-wssos-show-unchecked')) {
			var wssos_unchecked_value = $(this).data('wssos-show-unchecked')
			var wssos_unchecked_elm = $(parent_wssos).find('[data-wssos-value="' + wssos_unchecked_value + '"]')

			if (wssos_unchecked_elm.length === 0) wssos_unchecked_elm = $(parent_wssos).find('#' + wssos_unchecked_value)

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
	var selectVal = $(selectElm).val()
	$(selectElm).children().each(function (index, option) {
		var wssos_value = $(option).data('wssos-show')
		var parent_wssos = $(option).parentsUntil('.WSShowOnSelect').parent()[0]
		var wssos_elm = $(parent_wssos).find('[data-wssos-value="' + wssos_value + '"]')

		if (wssos_elm.length === 0) wssos_elm = $(parent_wssos).find('#' + wssos_value)

		if (option.selected || $(option).val() === selectVal) {
			wssos_elm.show(0)
			putAllTypesDataInName(wssos_elm)
		} else {
			wssos_elm.hide(0)
			putAllTypesNameInData(wssos_elm)
		}
	})

	$(selectElm).off('change')
	$(selectElm).on('change', function () {
		var wssos_show_elm = null
		$(this).children().each(function (index, option) {
			var wssos_value = $(option).data('wssos-show')
			var parent_wssos = $(this).parentsUntil('.WSShowOnSelect').parent()[0]
			var wssos_elm = $(parent_wssos).find('[data-wssos-value="' + wssos_value + '"]')

			if (wssos_elm.length === 0) wssos_elm = $(parent_wssos).find('#' + wssos_value)

			if (option.selected) {
				wssos_show_elm = wssos_elm
			} else {
				wssos_elm.hide(0)
				putAllTypesNameInData(wssos_elm)
			}
		})

		if (wssos_show_elm === null) return

		wssos_show_elm.show(0)
		putAllTypesDataInName(wssos_show_elm)
	})
}

function handleButton (btnElm) {
	var pre_wssos_value = $(this).data('wssos-show')
	var pre_parent_wssos = $(this).parentsUntil('.WSShowOnSelect').parent()[0]
	var pre_wssos_elm = $(pre_parent_wssos).find('[data-wssos-value="' + pre_wssos_value + '"]')

	if (pre_wssos_elm.length === 0) pre_wssos_elm = $(pre_parent_wssos).find('#' + pre_wssos_value)

	// set up the start and make sure the element is hidden
	$(pre_wssos_elm).hide(0)
	putAllTypesNameInData(pre_wssos_elm)

	$(btnElm).off('click')
	// add on click listener to the button
	$(btnElm).on('click', function (e) {
		var wssos_value = $(this).data('wssos-show')
		var parent_wssos = $(this).parentsUntil('.WSShowOnSelect').parent()[0]
		var wssos_elm = $(parent_wssos).find('[data-wssos-value="' + wssos_value + '"]')

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
 * @param elm
 */
function putNameAttrValueInDataset ($elm) {
	$.each($elm, function (index, elm) {
		if ($(elm).attr('name') !== '') {
			var name = $(elm).attr('name')
			if (name) {
				$(elm).attr('data-name-attribute', name)
				$(elm).removeAttr('name')
			}
		}
	})
}

/**
 * set the name attribute to the value of the data-name-attribute
 * @param elm
 */
function putDatasetValueBackInName ($elm) {
	$.each($elm, function (index, elm) {
		if ($(elm).attr('data-name-attribute') !== '') {
			var datasetName = $(elm).data('name-attribute')
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


