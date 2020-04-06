const PROP_ID = '#ws-form-properties';
let sortableOptionsList;
/**
 * set up the onclick function to see the inputs properties
 */
function setUpEditable() {
    $('.ws-form-col').on('click', function (e) {
        e.stopPropagation();
        e.preventDefault();
        $(PROP_ID).html("");
        let elm = getElementFromId(this.id);
        let attributes = elm.attr;
        attributes['type'] = elm.type;
        attributes['cols'] = elm.getColNr();
        let availableAttr = getAvailableAttrByType(elm.type);
        let elmArray = createElementsForAttrs(availableAttr, attributes, elm);
        let h2 = $('<h2>Input - properties  </h2>')[0];
        let info = createDocsElement(elm.type);
        $(h2).append(info);
        $(PROP_ID).append(h2);
        for ( let i = 0; i < elmArray.length; i++ ) {
            $(PROP_ID).append(elmArray[i]);
        }
        $(PROP_ID).append(createDeleteElementButton(elm.id));
        setOnchangeListenerEditInputs(this.id);
    });
}

/**
 * @onclick -> show the form attributes on click on the parent
 */
$('#parent-form-element').on('click', function (e) {
   e.preventDefault();
   e.stopPropagation();
   resetPropertiesElement();
});

/**
 * set up the onchange listeners for in the properties element
 * @onchange -> update the element class and the html on the preview
 * @param elementId
 */
function setOnchangeListenerEditInputs(elementId) {
    let elm = getElementFromId(elementId);
    if ( elm ) {
        $(PROP_ID).find('[data-ws-edit-input="true"]').each(function () {
           $(this).on('change', function () {
                let attr = $(this).data("ws-edit-attr");
                let value = $(this).val();
                if ( $(this).is("input[type='checkbox']") ) {
                    if ( $(this).is("input[type='checkbox']:checked") ) {
                        value = attr;
                    } else {
                        elm.removeAttr(attr);
                    }
                }
                if ( attr == "cols" ) {
                    elm.setCols(value);
                } else if ( attr === "type" ) {
                    elm.type = value;
                } else {
                    elm.changeAttr(attr, value);
                }
                $('#'+elementId).replaceWith(elm.getElement());
                setUpEditable();
           });
        });
    }
}

/**
 * make sure the form element attributes have an onchange event to update the formElement
 */
function setOnChangeListenerFormEditInputs() {
    $(PROP_ID).find('[data-ws-edit-input="true"]').each(function () {
        $(this).on('change', function () {
            let attr = $(this).data("ws-edit-attr");
            let value = $(this).val();
            if ( $(this).is("input[type='checkbox']") ) {
                if ( $(this).is("input[type='checkbox']:checked") ) {
                    value = attr;
                } else {
                    formElement.removeAttr(attr);
                }
            }

            if ( attr == "formname" ) {
                formElement.name = value;
            } else if ( attr == "type" ) {

            } else {
                formElement.changeAttr(attr, value);
            }
            if ( attr == 'formname' ) {
                $('#form-name-header')[0].textContent = value;
            }
            setUpEditable();
        });
    });
}

/**
 * create delete button for in the properties element
 * @onclick -> update the elements array, remove the element from the page and clear properties screen
 * @param id
 * @return {HTMLElement}
 */
function createDeleteElementButton(id = false) {
    if ( id ) {
        let div = document.createElement('div');
        $(div).addClass('text-md-right');
        let btn = document.createElement('button');
        btn.textContent = "Remove";
        $(btn).addClass("btn");
        $(btn).addClass("btn-danger");
        $(btn).on('click', function (e) {
           e.preventDefault();
            if ( confirm("Are you sure you want to delete this element?") ) {
                if ( $('#'+id).parent().children().length > 1 ) {
                    $('#' + id).remove();
                } else {
                    let parent = $('#' + id).parent();
                    $('#' + id).remove();
                    $(parent).remove();
                }
                $(PROP_ID).html("");

                let index = 0;
                for (let i = 0; i < elements.length; i++) {
                    if (elements[i].id == id) {
                        index = i;
                    }
                }
                elements.splice(index, 1);
            }
        });
        $(div).append(btn);
        return div;
    }
}

/**
 * get element class from the elements array with specified id
 * @param id
 * @return Element class
 */
function getElementFromId(id = false) {
    if ( id ) {
        for ( let i = 0; i < elements.length; i++ ) {
            if ( elements[i].id == id ) {
                return elements[i];
            }
        }
    } else {
        return false;
    }
}

/**
 * get the available attributes for in the properties element, check on input type
 * @param type
 * @return object -> differs between input type
 */
function getAvailableAttrByType(type) {
    switch (type) {
        case 'wscreate':
            return AvailableAttributes.getWSCreate();
        case 'wsedit':
            return AvailableAttributes.getWSEdit();
        case 'select':
            return AvailableAttributes.getSelectAttr();
        case 'button':
            return AvailableAttributes.getButtonAttr();
        case 'submit':
            return AvailableAttributes.getButtonAttr();
        case 'number':
            return AvailableAttributes.getInputAttr();
        case 'label':
            return AvailableAttributes.getLabelAttr();
        case 'file':
            return AvailableAttributes.getFileAttr();
        case 'form':
            return AvailableAttributes.getFormAttr();
        case 'wsemail':
            return AvailableAttributes.getWSEmail();
        default:
            return AvailableAttributes.getInputAttr();
    }
}

/**
 * create the inputs for in the properties element
 * @param attrList
 * @param valuesObj
 * @param objElm
 * @return {Array}
 */
function createElementsForAttrs(attrList, valuesObj, objElm = false) {
    let attrArray = attrList.array;
    let parent = [];
    const INPUT_TYPE = 'inputtype';
    const OPTIONS = 'options';
    for ( let i = 0; i < attrArray.length; i++ ) {
        let obj = attrList[attrArray[i]];
        let input_type = obj[INPUT_TYPE];
        let elm;
        let info = "";
        if ( attrArray[i] !== 'text' ) {
            info = createDocsElement(attrArray[i]);
        }

        if ( input_type == 'select' ) {
            let options = obj[OPTIONS];
            elm = createChildElement(attrArray[i] + ":", input_type, valuesObj[attrArray[i]], options);
        } else if ( input_type == 'optionsList') {
            elm = createOptionsListElement(objElm);
        } else {
            elm = createChildElement(attrArray[i] + ":", input_type, valuesObj[attrArray[i]]);
        }
        $(elm).find('label').append(info);
        parent.push(elm);
    }
    return parent;
}

/**
 * create an input element in the properties element
 * @param label_txt
 * @param input_type
 * @param value
 * @param options
 * @return {HTMLElement}
 */
function createChildElement(label_txt, input_type, value = "", options = false) {
    let elm = document.createElement('div');
    $(elm).addClass('form-row');
    if ( typeof value == 'undefined' ) {
        value = "";
    }
    let child;
    if ( options ) {
        child = new Select(input_type, {
            value: value
        });
        child.addDataAttr("ws-edit-input", "true");
        child.addDataAttr("ws-edit-attr", label_txt.slice(0, label_txt.indexOf(":")));
        for ( let i = 0; i < options.length; i++ ) {
            if ( options[i] == value ) {
                child.addOption(options[i], options[i], true);
            } else {
                child.addOption(options[i], options[i]);
            }
        }
    } else if ( input_type == 'checkbox' ) {
        child = new Element(input_type, {
            value: value
        });
        child.addDataAttr("ws-edit-input", "true");
        child.addDataAttr("ws-edit-attr", label_txt.slice(0, label_txt.indexOf(":")));
    } else {
        child = new Element(input_type,{
            value : value
        });
        child.addDataAttr("ws-edit-input", "true");
        child.addDataAttr("ws-edit-attr", label_txt.slice(0, label_txt.indexOf(":")));
    }
    let label = '<label>' + label_txt + '  </label>';
    $(elm).append($(label)[0]);
    $(elm).append(child.input());
    return elm;
}

/**
 * create the options list element for in the properties element
 * @param objElm
 * @return {HTMLElement}
 */
function createOptionsListElement(objElm) {
    let parentElement = document.createElement("DIV");
    $(parentElement).addClass("form-row");
    let label = '<label>Option list:</label>';
    let openModalBtn = document.createElement('button');
    openModalBtn.textContent = 'Options';
    $(openModalBtn).attr('data-izimodal-open', '#custom-option-modal');
    $(openModalBtn).addClass('button');
    $(openModalBtn).addClass('save');
    $(openModalBtn).get(0).style.background = '#457aaa';
    $(openModalBtn).click(function (e) {
       $('#option-modal-body').html('');
       $('#option-modal-body').append($(createOptionTable())[0]);
        if ( objElm.countOptions > 0 ) {
            for ( let i in objElm.options ) {
                var dataid = i;
                $('#modal-option-table-body').append(createAlreadyExistingOptions(objElm, objElm.options[i].attr["value"], objElm.options[i].attr["text"], dataid)[0]);
                resetOptionListToOrder();
            }
            $('button[data-remove-option]').click(function (e) {
                objElm.removeOption($(this).parent().parent().index() + 1);
                $('#'+objElm.id).replaceWith(objElm.getElement());
                $(this).parent().parent().remove();
            });
            $('input[data-wsoptionlisttype]').on('change', function (e) {
                var parent = $(this).parentsUntil('tr').parent();
                objElm.changeAttrOption(($(parent).index() + 1), $(this).attr('data-wsoptionlisttype'), $(this).val());
                $('#'+objElm.id).replaceWith(objElm.getElement());
            });
        }
       $('#modal-add-option').click(function (e) {
           var tableElm = $(createTableElement())[0];
           objElm.addOption('', '');
           $(tableElm).attr('data-id', objElm.countOptions);
           $('#modal-option-table-body').append(tableElm);
           $('button[data-remove-option]').click(function (e) {
               objElm.removeOption($(this).parent().parent().index() + 1);
               $('#'+objElm.id).replaceWith(objElm.getElement());
               $(this).parent().parent().remove();
           });
           $('input[data-wsoptionlisttype]').on('change', function (e) {
               var parent = $(this).parentsUntil('tr').parent();
               objElm.changeAttrOption(($(parent).index() + 1), $(this).attr('data-wsoptionlisttype'), $(this).val());
               $('#'+objElm.id).replaceWith(objElm.getElement());
           });
       });
       Sortable.create($('#modal-option-table-body')[0], {
           animation: 150,
           onSort: function (e) {
               let order = this.toArray();
               objElm.changeOptionsOrder(order);
               resetOptionListToOrder();
               $('#'+objElm.id).replaceWith(objElm.getElement());
           },
           onClosed: function (e) {
               $('#'+objElm.id).replaceWith(objElm.getElement());
           }
       });
    });
    $(parentElement).append($(label)[0]);
    $(parentElement).append(openModalBtn);
    return parentElement;
}


/**
 * resets the order of data-id's in the option list element
 */
function resetOptionListToOrder() {
    let listitems = $(PROP_ID).find("tr[data-id]");
    let i = 1;
    $(listitems).each(function (k, item) {
        item.dataset['id'] = i;
        i++;
    });
}

/**
 * create an edit options element of an existing option
 * @param elmObj
 * @param value
 * @param text
 * @return {HTMLElement}
 */
function createAlreadyExistingOptions(elmObj, value, text, dataid) {
    let listitem = $(createTableElement(dataid));
    $(listitem).find("input[data-wsoptionlisttype]").each(function () {
       let type = $(this).data("wsoptionlisttype");
       switch (type) {
           case "value":
               $(this).val(value);
               break;
           case "text":
               $(this).val(text);
               break;
           default:
               break;
       }
    });
    return listitem;
}

/**
 * reset the Attribute element with the formElement attr
 */
function resetPropertiesElement() {
    $(PROP_ID).html("");
    if ( typeof formElement == 'object' ) {
        let attributes = formElement.attr;
        attributes['formname'] = formElement.name;
        let availableAttr = getAvailableAttrByType(formElement.type);
        let elmArray = createElementsForAttrs(availableAttr, attributes, formElement);
        let h2 = $('<h2>Form - properties</h2>')[0];
        let info = createDocsElement('wsform');
        $(h2).append(info);
        $(PROP_ID).append(h2);
        for ( let i = 0; i < elmArray.length; i++ ) {
            $(PROP_ID).append(elmArray[i]);
        }
        setOnChangeListenerFormEditInputs();
    }

}

function createOptionTable() {
    return `<div class="container">
    <div class="row">
        <table class="table table-light table-bordered">
            <thead>
                <tr>
                    <th scope="col">Value</th>
                    <th scope="col">Text</th>
                    <th scope="col"><button class="btn btn--blue" id="modal-add-option">+</button></th>
                </tr>
            </thead>
            <tbody id="modal-option-table-body">
                  
            </tbody>
        </table>
    </div>
</div>`;
}

function createTableElement(dataid = '') {
    return `<tr data-id="${dataid}">
    <td><input type="text" class="form-control" placeholder="value" data-wsoptionlisttype="value"></td>
    <td><input type="text" class="form-control" placeholder="text" data-wsoptionlisttype="text"></td>
    <td><button class="button save" style="background: darkred" data-remove-option="">x </button></td>
</tr>`;
}

function createDocsElement(name) {
    let elm = "";
    let links = AvailableAttributes.links();
    if ( links[name] ) {
        elm = document.createElement("div");
        elm.style = "display: inline; margin-left: 3px;";
        let link = links["general"] + links[name];
        $('<a>', {
            target: '_blank',
            href: link,
            role: 'button'
        }).append('<i class="fa fa-question-circle"></i>').appendTo(elm);
    }
    return elm;
}