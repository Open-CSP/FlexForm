let idUnifier = 0;

/**
 *
 * @param selector {object}
 * @param options {object}
 * @constructor
 */
const WsInstance = function (selector, options) {
    const _ = this;

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

    _.timesCopied = 0;

    /**
     * returns unique id based on timestamp
     * @returns {number}
     */
    _.getUniqueId = () => new Date().getTime() + idUnifier++;


    // update settings with custom options
    Object.assign(_.settings, options);
    Object.assign(_.settings, {selector: selector});

    // fetch elements from dom
    _.wrapper = $(_.settings.selector);

    if ( _.wrapper.length > 1 ) { // multiple wrapper elements with selector
        let instanceArray = [];
        $.each(_.wrapper, function(i, w) {
            instanceArray.push(new WsInstance(w, _.settings));
        });
        return instanceArray;
    }

    _.saveField = _.wrapper.find(_.settings.textarea);
    _.list = _.wrapper.find(_.settings.list);
    _.clone = _.wrapper.find(_.settings.copy);
    _.sortable = null;

    if ( !$(_.saveField).is('textarea') ) {
        _.saveField = $(_.saveField).find('textarea');
    }



    /**
     * get content from textarea and copy into list
     */
    _.convertPredefinedToInstances = () => {
        let name_array = [];
        let value_array = [];

        let textarea_content = _.saveField.val();
        let textarea_items = textarea_content.split('{{').filter((v) => v);

        if ( textarea_items.length === 0 ) {
            let clone = _.getCloneInstance();
            let el = _.getCloneElementHandled(clone);
            _.list.append(el);
        }

        $.each(textarea_items, function(i, val) {
            let content_array = val.split('\n').filter((v) => v.includes('='));

            $.each(content_array, function (index, item) {
                name_array.push(item.split('=')[0].slice(1));
                value_array.push(item.split('=')[1].split('}}')[0]);
            });

            handlePredefinedData(name_array, value_array);
            name_array = [];
            value_array = [];
        });

    }

    /**
     * handles the predefined data from textarea and instantiates clone
     * @param names
     * @param values
     */
    const handlePredefinedData = (names, values) => {
        let clone = _.getCloneInstance();

        for ( let i = 0; i < names.length; i++ ) {
            $(clone).find('input[name*="' + names[i] +'"]').each(function (index, input) {
                switch(input.getAttribute('type')) {
                    case 'radio':
                        input.checked = input.value === values[i];
                        break;
                    case 'checkbox':
                        input.checked = (values[i] === 'Yes' || values[i] === 'true');
                        break;
                    default:
                        input.setAttribute('value', values[i]);
                        input.value = values[i];
                }
            });

            $(clone).find('textarea[name*="'+ names[i] + '"]').each(function (index, textarea) {
                textarea.value = values[i];
                textarea.setAttribute('value', values[i]);
            });

            $(clone).find('select[name*="' + names[i] + '"]').each(function(index, select) {
                if (values[i].indexOf(',') !== -1) {
                    let multipleSelect2Values = values[i].split(',');
                    let optionList = select.children;
                    for (let k = 0; k < optionList.length; k++) {
                        optionList[k].selected = multipleSelect2Values.includes(optionList[k].value);
                    }
                } else {
                    let optionSelected = $(select).find("option[value='" + values[i] + "']");
                    if ( optionSelected.length > 0 && values[i] !== "" ) {
                        optionSelected.prop("selected", "selected");
                    } else if (optionSelected.length === 0 ) {
                        if ( $(select).first().val() !== "" ) {
                            $(select).insertBefore('<option value="" selected="selected">""</option>', select.children[0]);
                        }
                    }
                }

                if ( values[i] !== "" ) {
                    select.setAttribute('value', values[i]);
                    select.value = values[i];
                }
            });
        }

        let element = _.getCloneElementHandled(clone);
        _.list.append(element);
    }

    /**
     * handles events for buttons and classes of clone
     * @param clone {HTMLElement}
     * @returns {HTMLElement}
     */
    _.getCloneElementHandled = (clone) => {
        let del_btn = $(clone).find(_.settings.removeButtonClass);
        let add_button = $(clone).find(_.settings.addButtonClass);
        const id = 'copy_' +  _.getUniqueId();

        console.log({
            del_btn: del_btn,
            add_button: add_button,
            id: id,
            clone: clone
        });

        $(clone).prop('id', id);
        $(del_btn).off('click');
        $(del_btn).on('click', function (e) {
            _.removeInstance(id);
        });

        $(add_button).off('click');
        $(add_button).on('click', function(e) {
            e.preventDefault();
            _.addAboveInstance(id);
        });

        return clone;
    }

    /**
     * removes instance from list
     * @param id {string}
     */
    _.removeInstance = (id) => {
        $('#' + id).remove();
    }

    /**
     * adds instance above instance
     * @param id {string}
     */
    _.addAboveInstance = (id) => {
        let element = _.getCloneElementHandled(_.getCloneInstance());
        $(element).insertBefore($('#' + id));
    }

    /**
     * clones a instance of the clone element
     * @returns {HTMLElement }
     */
    _.getCloneInstance = () => {
        return _.clone.clone().removeClass('WSmultipleTemplateMain').addClass('WSmultipleTemplateInstance');
    }

    _.handleIntegrations = () => {
        // make functions for eventual integrations like select2 or wssos
    }

    _.save = () => {
        let saveString = '';
        _.list.find('.WSmultipleTemplateInstance').each(function (i, instance) {
            let valuesObj = {};
            $(instance).find('[name*="WS multiple template"]').each(function (index, input) {
                let name = returnValueBetweenBrackets(input.name);
                switch (input.type) {
                    case 'checkbox':
                        if ( input.checked ) {
                            valuesObj[name] = input.value;
                        } else {
                            valuesObj[name] = '';
                        }
                        break;
                    case 'radio':
                        if  ( input.checked ) {
                            valuesObj[name] = input.value;
                        }
                        break;
                    default:
                        valuesObj[name] = input.value;
                        break;
                }

            });
            saveString += createSaveStringForInstance(valuesObj);
        });

        _.saveField.val(saveString);
    }

    const returnValueBetweenBrackets = (str) => {
        let n = str.indexOf("[");
        let x = str.indexOf(']');
        return str.slice(n + 1, x);
    }

    /**
     * create string to save in the textarea
     * @param obj {object}
     * @returns {string}
     */
    const createSaveStringForInstance = (obj) => {
        let returnStr = `{{${_.saveField.data('template')}\n`;

        $.each(obj, function(k, v) {
            if ( typeof v === 'array' ) {
                returnStr += `|${k}=${v.join(',')}\n`;
            } else {
                returnStr += `|${k}=${v}\n`;
            }
        });

        return returnStr + '}}';
    }

    /**
     * init function
     */
    _.init = () => {
        if ( _.wrapper.length === 0 ) {
            console.error("Selector is not active on this page");
            return;
        }

        if ( _.list.length === 0 ) {
            console.error("No selector for the list element present");
            return;
        }

        if ( _.saveField.length === 0 ) {
            console.error("No selector for the textarea is present");
            return;
        }

        if ( _.clone.length === 0 ) {
            console.error("No selector for the element that needs to be copied");
            return;
        }

        if ( _.settings.draggable ) {
            var extensionPath = mw.config.get('wgExtensionAssetsPath');
            $.getScript( extensionPath + '/WSForm/modules/instances/Sortable.min.js').done(function() {
                _.sortable = Sortable.create(_.list[0], {
                    animation: 150,
                    handle: _.settings.handleClass
                });
            });
        }

        _.convertPredefinedToInstances();
    }

    _.init();

    return _;
}