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
	_.getUniqueId = () => new Date().getTime() + idUnifier

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
				let template = item.slice(0, item.indexOf('='));
				let value = item.slice(item.indexOf('=') + 1);

				// check if the last character is a new line, then remove that
				if ( value[value.length-1] === '\n') {
					value = value.slice(0, -1)
				}

				name_array.push(template)
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

					// check if token field
					if ( $(select).children().val() === '' && $(select).data('inputtype') === 'ws-select2' ) {
						getPredefinedOptionsTokenField(select, multipleSelect2Values);
					} else {
						for (let k = 0; k < optionList.length; k++) {
							optionList[k].selected = multipleSelect2Values.includes(optionList[k].value)
						}
					}
				} else {
					let optionSelected = $(select).find('option[value=\'' + values[i] + '\']')
					if (optionSelected.length > 0 && values[i] !== '') {
						optionSelected.prop('selected', 'selected')
					} else if (optionSelected.length === 0 && $(select).data('inputtype') !== 'ws-select2') {
						if ($(select).children().val() !== '') {
							$(select).insertBefore('<option value="" selected="selected">""</option>', select.children[0])
						}
					} else if ($(select).data('inputtype') === 'ws-select2') {
						if ($(select).children().val() === '' && values[i]) {
							// $(select).append(`<option value="${values[i]}" selected="selected">${values[i]}</option>`)
							getPredefinedOptionsTokenField(select, [values[i]]);
						}
					}
				}

				if (values[i] !== '') {
					select.setAttribute('value', values[i])
					select.value = values[i]
					$(select).val(values[i])
				}
			})
		}

		let element = _.getCloneElementHandled(clone)
		_.list.append(element)
		_.handleIntegrations(element, true)
	}

	const getPredefinedOptionsTokenField = (select, values) => {
		let query = $(select).next().val()
		query = query.slice((query.indexOf('query=') + 6), query.indexOf("=';"))
		query = atob(query)

		let return_text = ''
		if ( query.indexOf('returntext') > -1 ) {
			return_text =  query.slice(query.indexOf('returntext') + 11, query.indexOf(')', query.indexOf('returntext')))
		}
		query = `[[${values.join('||')}]]|?${return_text}`

		let params = {
			action: 'ask',
			query: query,
			format: 'json'
		}
		mw.loader.using( 'mediawiki.api', function() {
			new mw.Api().get(params).done(data => {
				const results = data.query.results;
				if (!results) {
					mw.notify('something went wrong collecting pre defined tokens', { type: 'error' })
					return
				}

				$.each(results, (k, v) => {
					let title = ''
					const checkPrintouts = v.printouts[return_text] ? v.printouts[return_text].length > 0 : false
					if (checkPrintouts) {
						title = v.printouts[return_text][0]
					} else if (v.displaytitle) {
						title = v.displaytitle
					} else {
						title = k
					}
					$(select).append(`<option value="${k}" selected="selected">${title}</option>`)
				})
			})
		})
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

		clone.find('select[data-inputtype="ws-select2"]').each((i, select) => {
			if ($(select).next().is('span')) {
				$(select).next().remove();
			}
		})

		clone.find('[data-ff-required=true]').prop('required', true)

		return clone
	}

	_.handleIntegrations = (element, isPreDefined = false) => {
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

				$(select).attr('data-wsselect2id', select2id + '_' + idUnifier)
				$(select).attr('id', select2id + '_' + idUnifier)

				let sibling = $(select).siblings(`input[id="select2options-${select2id}"]`)[0]
				if (!sibling) return;
				sibling.id = $(sibling).attr('id') + '_' + idUnifier


				let statement = sibling.value
				statement = statement.replaceAll(`'#${select2id}'`, `'#${select2id}_${idUnifier}'`)
				sibling.value = statement
				if (typeof $.fn.select2 === 'function') Function(statement)()
			})
		}

		if ($(element).find('.ve-area-wrapper').length > 0 && !isPreDefined) {
			$(element).find('.ve-area-wrapper').each((i, wrapper) => {
				wrapper.innerHTML = $(wrapper).find('textarea')[0].outerHTML;
				$(wrapper).find('textarea').show(0)
				$(wrapper).find('textarea').applyVisualEditor()
			})
		}

		idUnifier++;
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

		const saveAllInstances = () => {
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


		let veWrapperLength = _.list.find('.ve-area-wrapper').length;

		if (veWrapperLength > 0) {
			mw.loader.using( 'mediawiki.api', function() {
				const api = new mw.Api()

				// loop through all VisualEditor instances to handle the convert from html to wikitext
				$.each($.fn.getVEInstances(), (i, ve) => {
					// check if the last element in the array has class WSmultipleTemplateInstance to continue
					if ($(Array.from(ve.$node.parentsUntil('.WSmultipleTemplateList')).at(-1)).hasClass('WSmultipleTemplateInstance')) {
						// make api post to convert the html to wikitext
						api.post({
							action: 'veforall-parsoid-utils',
							from: 'html',
							to: 'wikitext',
							content: ve.target.getSurface().getHtml(),
							title: mw.config.get('wgPageName').split(/(\\|\/)/g).pop()
						}).then(data => {
							// replace pipes and set the content in the textarea
							const text = data['veforall-parsoid-utils'].content
							let esc = replacePipes(text)
							$(ve.$node).val(esc)

							veWrapperLength--;
							if (veWrapperLength === 0) {
								saveAllInstances()
							}
						})
					}
				})
			})
		} else {
			saveAllInstances()
		}
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


	const setRequiredFieldToDataset = () => {
		$(_.clone).find(':required').each((i, input) => {
			$(input).prop('required', false)
			$(input).attr('data-ff-required', true)
		})
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
					handle: _.settings.handleClass,
					forceFallback: true,
					touchStartThreshold: 10
				})
			})
		}

		setRequiredFieldToDataset()

		_.wrapper.find('.WSmultipleTemplateAddBelow').on('click', function (e) {
			e.preventDefault()
			e.stopPropagation()
			_.addToBottom()
		})

		if ($(_.clone).find('.ve-area-wrapper').length > 0) {
			waitForVE(() => {
				wachtff(() => {
					_.convertPredefinedToInstances()
					initializeVE()
				}, true)
			})
		} else {
			wachtff(_.convertPredefinedToInstances, true)
		}

		// fire hook when instances are done
		mw.hook('flexform.instance.done').fire(_);
	}

	_.init()

	return _
}
