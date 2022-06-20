let idUnifier = 0

/**
 *
 * @param selector {object}
 * @param options {object}
 * @constructor
 */
const WsInstance = function (selector, options) {
	const _ = this

	// default settings
	_.settings = {
		draggable: false,
		addButtonClass: '.WSmultipleTemplateAddAbove',
		removeButtonClass: '.WSmultipleTemplateDel',
		handleClass: '.ws-sortable-handle',
		selector: '.WSmultipleTemplateWrapper',
		textarea: '.WSmultipleTemplateField',
		list: '.WSmultipleTemplateList',
		copy: '.WSmultipleTemplateMain'
	}

	_.timesCopied = 0

	/**
	 * returns unique id based on timestamp
	 * @returns {number}
	 */
	_.getUniqueId = () => new Date().getTime() + idUnifier++

	// update settings with custom options
	Object.assign(_.settings, options)
	Object.assign(_.settings, { selector: selector })

	// fetch elements from dom
	_.wrapper = $(_.settings.selector)

	if (_.wrapper.length > 1) { // multiple wrapper elements with selector
		let instanceArray = []
		$.each(_.wrapper, function (i, w) {
			instanceArray.push(new WsInstance(w, _.settings))
		})
		return instanceArray
	}

	_.saveField = _.wrapper.find(_.settings.textarea)
	_.list = _.wrapper.find(_.settings.list)
	_.clone = _.wrapper.find(_.settings.copy)
	_.sortable = null

	if (!$(_.saveField).is('textarea')) {
		_.saveField = $(_.saveField).find('textarea')
	}

	/**
	 * get content from textarea and copy into list
	 */
	_.convertPredefinedToInstances = () => {
		let name_array = []
		let value_array = []

		let textarea_content = _.saveField.val()
		let textarea_items = textarea_content.split('{{').filter((v) => v)

		$.each(textarea_items, function (i, val) {
			let content_array = val.split('}}').join('').split('|').filter(v => v.includes('='))
			$.each(content_array, function (index, item) {
				const s = item.split('=')
				let value = s[1]

				// check if the last character is a new line, then remove that
				if ( value[value.length-1] === '\n') {
					value = value.slice(0, -1)
				}

				name_array.push(s[0])
				value_array.push(value)
			})


			handlePredefinedData(name_array, value_array)
			name_array = []
			value_array = []
		})
	}

	/**
	 * handles the predefined data from textarea and instantiates clone
	 * @param names
	 * @param values
	 */
	const handlePredefinedData = (names, values) => {
		let clone = _.getCloneInstance()

		for (let i = 0; i < names.length; i++) {
			$(clone).find('input[name*="' + names[i] + '"]').each(function (index, input) {
				switch (input.getAttribute('type')) {
					case 'radio':
						input.checked = input.value === values[i]
						break
					case 'checkbox':
						input.checked = (values[i] === 'Yes' || values[i] === 'true' || values[i] === 'checked')
						break
					default:
						input.setAttribute('value', values[i])
						input.value = values[i]
				}
			})

			$(clone).find('textarea[name*="' + names[i] + '"]').each(function (index, textarea) {
				textarea.value = values[i]
				textarea.setAttribute('value', values[i])
			})

			$(clone).find('select[name*="' + names[i] + '"]').each(function (index, select) {
				if (values[i].indexOf(',') !== -1) {
					let multipleSelect2Values = values[i].split(',')
					let optionList = select.children
					for (let k = 0; k < optionList.length; k++) {
						optionList[k].selected = multipleSelect2Values.includes(optionList[k].value)
					}
				} else {
					let optionSelected = $(select).find('option[value=\'' + values[i] + '\']')
					if (optionSelected.length > 0 && values[i] !== '') {
						optionSelected.prop('selected', 'selected')
					} else if (optionSelected.length === 0) {
						if ($(select).first().val() !== '') {
							$(select).insertBefore('<option value="" selected="selected">""</option>', select.children[0])
						}
					}
				}

				if (values[i] !== '') {
					select.setAttribute('value', values[i])
					select.value = values[i]
				}
			})
		}

		let element = _.getCloneElementHandled(clone)
		_.list.append(element)
		_.handleIntegrations(element)
	}

	/**
	 * handles events for buttons and classes of clone
	 * @param clone {HTMLElement}
	 * @returns {HTMLElement}
	 */
	_.getCloneElementHandled = (clone) => {
		let del_btn = $(clone).find(_.settings.removeButtonClass)
		let add_button = $(clone).find(_.settings.addButtonClass)
		const id = 'copy_' + _.getUniqueId()

		$(clone).prop('id', id)
		$(del_btn).off('click')
		$(del_btn).on('click', function (e) {
			_.removeInstance(id)
		})

		$(add_button).off('click')
		$(add_button).on('click', function (e) {
			e.preventDefault()
			_.addAboveInstance(id)
		})

		return clone
	}

	/**
	 * removes instance from list
	 * @param id {string}
	 */
	_.removeInstance = (id) => {
		$('#' + id).remove()
	}

	/**
	 * adds instance above instance
	 * @param id {string}
	 */
	_.addAboveInstance = (id) => {
		let element = _.getCloneElementHandled(_.getCloneInstance())
		$(element).insertBefore($('#' + id))
		_.handleIntegrations(element)
	}

	/**
	 * clones a instance of the clone element
	 * @returns {HTMLElement }
	 */
	_.getCloneInstance = () => {
		const clone = _.clone.clone().removeClass('WSmultipleTemplateMain').addClass('WSmultipleTemplateInstance')

		// loop through all input, textarea and select types to handle duplicated ids
		clone.find('input,textarea,select').each(function (i, input) {
			if (!$(input).attr('id')) return

			const id = $(input).attr('id')
			if (id.includes('select2options-') && input.type === 'hidden') return;
			$(input).attr('id', id + '_' + idUnifier)
			clone.find(`label[for="${id}"]`).attr('for', id + '_' + idUnifier)
		})

		// handle radio button names
		clone.find('input[type="radio"]').each(function(i, radio) {
			radio.name += '___' + idUnifier
		})

		return clone
	}

	_.handleIntegrations = (element) => {
		// make functions for eventual integrations like select2 or wssos
		if (typeof WsShowOnSelect === 'function') WsShowOnSelect()

		const checkForSelect2 = function (n) {
			if (n > 25) return
			if (typeof $.fn.select2 !== 'function') {
				setTimeout(function () {
					checkForSelect2(n + 1)
				}, 250)
			}
		}


		if ($(element).find('[data-inputtype="ws-select2"]').length > 0) {
			checkForSelect2(0)

			$(element).find('[data-wsselect2id]').each(function (i, select) {
				let select2id = $(select).attr('data-wsselect2id')

				$(select).attr('data-wsselect2id', select2id + idUnifier)
				$(select).attr('id', select2id + idUnifier)

				let sibling = $(select).siblings(`input[id="select2options-${select2id}"]`)[0]
				if (!sibling) return;
				sibling.id = $(sibling).attr('id') + idUnifier


				let statement = sibling.value
				statement = statement.replace(select2id, select2id + idUnifier)
				sibling.value = statement
				if (typeof $.fn.select2 === 'function') Function(statement)()
			})
		}
	}

	/**
	 * add instance to bottom
	 */
	_.addToBottom = () => {
		let element = _.getCloneElementHandled(_.getCloneInstance())
		$(_.list).append(element)
		_.handleIntegrations(element)
	}

	/**
	 * saves the form
	 */
	_.save = () => {
		let saveString = ''

		// remove the names in the copy element
		_.clone.find('input,select,textarea').each(function(index, input) {
			// name is later in this function removed, when the save event occurs multiple times it needs to get
			// the name in another way
			let name = input.name
			if (name === '') name = input.getAttribute('data-name')

			// check if name is set, else return
			if (!name) return

			input.removeAttribute('name');
			input.setAttribute('data-name', name);
		})

		// loop through all instances in the list
		_.list.find('.WSmultipleTemplateInstance').each(function (i, instance) {
			let valuesObj = {}

			// loop through every input selectbox and textarea in this instance
			$(instance).find('input,select,textarea').each(function (index, input) {
				// name is later in this function removed, when the save event occurs multiple times it needs to get
				// the name in another way
				let name = input.name
				if (name === '') name = input.getAttribute('data-name')

				// check if name is set, else return
				if (!name) return

				// remove brackets at the end
				name = removeBracketsAtEnd(name)

				// switch through all different types
				switch (input.type) {
					case 'checkbox':
						if (input.checked) {
							valuesObj[name] = input.value
						} else {
							valuesObj[name] = ''
						}
						break
					case 'radio':
						if (input.checked) {
							// remove added unique name attribute
							name = name.substring(0, name.indexOf('___'))
							valuesObj[name] = input.value
						}
						break
					case 'hidden':
						return
					default:
						valuesObj[name] = input.value
						break
				}
				// remove name attr otherwise it will be send along with wsform
				input.removeAttribute('name')

				// set name in data attribute so the name is still available
				input.setAttribute('data-name', name)
			})
			saveString += createSaveStringForInstance(valuesObj)
		})

		_.saveField.val(saveString)
	}

	/**
	 * Removes the brackets at the end of the string
	 * @param str {string}
	 * @returns {string}
	 */
	const removeBracketsAtEnd = (str) => {
		if (str.length > 2) {
			if (str[str.length - 2] === '[' && str[str.length - 1]) {
				str = str.substring(0, str.length - 2)
			}
		}
		return str
	}

	/**
	 * create string to save in the textarea
	 * @param obj {object}
	 * @returns {string}
	 */
	const createSaveStringForInstance = (obj) => {
		let returnStr = `{{${_.saveField.data('template')}\n`

		$.each(obj, function (k, v) {
			if (typeof v === 'array') {
				returnStr += `|${k}=${v.join(',')}\n`
			} else {
				returnStr += `|${k}=${v}\n`
			}
		})

		return returnStr + '}}'
	}

	/**
	 * init function
	 */
	_.init = () => {
		if (_.wrapper.length === 0) {
			console.error('Selector is not active on this page')
			return
		}

		if (_.list.length === 0) {
			console.error('No selector for the list element present')
			return
		}

		if (_.saveField.length === 0) {
			console.error('No selector for the textarea is present')
			return
		}

		if (_.clone.length === 0) {
			console.error('No selector for the element that needs to be copied')
			return
		}

		if (_.settings.draggable) {
			var extensionPath = mw.config.get('wgExtensionAssetsPath')
			$.getScript(extensionPath + '/FlexForm/Modules/instances/Sortable.min.js').done(function () {
				_.sortable = Sortable.create(_.list[0], {
					animation: 150,
					handle: _.settings.handleClass
				})
			})
		}

		_.wrapper.find('.WSmultipleTemplateAddBelow').on('click', function (e) {
			e.preventDefault()
			e.stopPropagation()
			_.addToBottom()
		})

		if ($(_.clone).find('.ve-area-wrapper').length > 0) {
			// // console.log(_.clone)
			// $(_.clone).find('.ve-area-wrapper')[0].innerHTML = $(_.clone).find('.ve-area-wrapper textarea').html()
			// $(_.clone).find('.ve-area-wrapper textarea').removeClass('oo-ui-texture-pending')
			// $(_.clone).find('.ve-area-wrapper')
			waitForVE(_.convertPredefinedToInstances)
		} else {
			_.convertPredefinedToInstances()
		}

	}

	_.init()

	return _
}
