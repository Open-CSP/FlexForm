let formElement;
let elements = [];
let count = 0;
let filesArray = [];

/**
 * add element to the elements array and return this Element class
 * @param type
 * @return {Element | Label | Select}
 */
function addElement(type = false) {
    count++;
    let attr = {
        placeholder: 'Placeholder'
    };
    let elm;
    if ( type === false ) {
        elm = new Element("text", attr);
    } else if( type === "select" ) {
        elm = new Select(type, attr);
    } else if ( type === "button" ) {
        attr.text = attr.placeholder;
        delete attr.placeholder;
        attr.class = 'btn btn-black';
        elm = new Element(type, attr);
    } else if ( type === "label" ) {
        attr.text = "Text";
        delete attr.placeholder;
        delete attr.class;
        elm = new Label(type, attr);
    } else if ( type === 'wscreate') {
        delete attr.placeholder;
        elm = new Wscreate(type, attr);
    } else if ( type === 'wsedit' ) {
        delete attr.placeholder;
        elm =  new Wsedit(type, attr);
    } else if ( type === 'wsemail' ) {
        delete attr.placeholder;
        elm =  new Wsemail(type, attr);
    } else {
        elm = new Element(type, attr);
    }

    //$('.sortable').append(elm.getElement());
    elements.push(elm);
    return elm;
}

/**
 * removes element with specified id
 * @param id
 */
function removeElement(id = false) {
    if ( id ) {
        let index = 0;
        for (let i = 0; i < elements.length; i++) {
            if (elements[i].id == id) {
                index = i;
            }
        }

        if ( $('#'+id).parent().children().length > 2) {
            $('#'+id).remove();
        } else {
            let parent = $('#'+id).parent();
            $('#' + id).remove();
            $(parent).remove();
        }
        elements.splice(index, 1);
    }
}


/**
 *
 * @param milliseconds
 * @return {Promise<any>}
 */
function sleep(milliseconds) {
    return new Promise(resolve => {
        setTimeout(() => {
            resolve('resolved');
        }, milliseconds);
    });
}

/**
 * @class Sortable
 * create the sortable class for the different input types to choose from
 */
let pull = Sortable.create($('#ws-sidemenu')[0], {
    group: {
        name: 'controls',
        pull: 'clone',
        put: false
    },
    animation: 150,
    sort: false
});

/**
 * @class Sortable
 * create the sortable class for the list with row elements
 */
let drop = Sortable.create($('#ws-sortable')[0], {
    group: {
        name: 'main',
        put: true,
        pull: ['main']
    },
    direction: 'vertical',
    animation: 150,
    sort: true,
    bubbleScroll: true
});

/**
 * set up the add listener for the drop list
 * @var drop is the list with row elements
 * @class Sortable
 */
drop.option('onAdd', function (e) {
    let parsedElm = changeToRealLayout(e.item);
    let d = new Date();
    let dataId = 'data-id="ws-form-row-' + d.getTime() + '"';
    let container;
    if ( $(e.item).data('input-type') === 'wscreate' || $(e.item).data('input-type') === 'wsedit' ) {
        container = '<div class="row form-row ws-form-row editor-hidden" ' + dataId + '></div>';
    } else {
        container = '<div class="row form-row ws-form-row editor-not-hidden" ' + dataId + '></div>';
    }
    let clonediv = createCloneElement(dataId);
    if ( $(e.item).hasClass('ws-form-col' ) ) {
        let elmObj = getElementFromId(parsedElm.id);
        $(e.item).replaceWith($(container).append(elmObj.getElement().append(clonediv)));
    } else {
        $(e.item).replaceWith($(container).append(parsedElm).append(clonediv));
    }

    setNestedSortable();
    setUpEditable();
});


/**
 * Make sures the selected item that is dragged from the left types list has the right layout
 *
 * @param item -> the item that is being dragged
 * @return item what is going to be added in the list
 */
function changeToRealLayout(item) {
    let input_type = $(item).data('input-type');
    if ( input_type != "" && input_type ) {
        let elm = addElement(input_type);
        return elm.getElement();
    } else if ( $(item).hasClass('ws-form-col') ) {
        return item;
    }
}

/**
 * change attribute of an element with id
 * @param id
 * @param key
 * @param value
 */
function changeElementAttr(id, key, value) {
    for (let i = 0; i < elements.length; i++ ) {
        if ( elements[i].id == id ) {
            elements[i].changeAttr(key, value);
        }
    }
}



setNestedSortable();


/**
 * Set up the elements that needs to be sorted in a row
 * @class Sortable
 */
function setNestedSortable() {
    $('.ws-form-row').each(function() {
        Sortable.create(this, {
            group: {
                name: 'nested',
                put: ['main', 'nested'],
                pull: true
            },
            filter: '.clone-element',
            fallbackOnBody: false,
            direction: 'horizontal',
            swapThreshold: 0.65,
            animation: 150,
            sort: true,
            bubbleScroll: true,
            onAdd: function (e) {
                if ( $(e.item).hasClass('ws-form-row') ) {
                    let child = $(e.item).children();
                    $(e.item).replaceWith(child);
                }
                setUpEditable();
                //var element = changeToRealLayout(e.item);
                //$(e.item).replaceWith(element);
            },
            onRemove: function (e) {
                if ( $(this.el).children().length <= 1 ) {
                    $(this.el).remove();
                }
            }
        })
    });
}

/**
 * @param array with updated order of the elements
 * @returns new element array with new order and the Elements classes
 */
function updateElementsArray(array = []) {
    let tempArray = [];

    for ( let i = 0; i < array.length; i++ ) {
        for ( let k = 0; k < elements.length; k++ ) {
            if ( elements[k].id == array[i] ) {
                tempArray[i] = elements[k];
            }
        }
    }

    return tempArray;
}

function copyRenderTextToClipboard() {
    $('#render-ta')[0].select();
    document.execCommand('copy');
    showSuccessalert('Copied the code clipboard!');
}

/**
 * @onclick function for the render button
 */
$('#ws-render-btn').on('click', function (e) {
    e.preventDefault();
    $('#render-ta').val(getRenderText(false));
    copyRenderTextToClipboard();
});

/**
 * @onclick function to get the mediawiki notation
 */
$('#render-to-wiki').on('click', function (e) {
    e.preventDefault();
    $('#render-ta').val(getRenderText(true));
    copyRenderTextToClipboard();
});

/**
 * @onclick function to get the wsform notation
 */
$('#render-to-wsform').on('click', function (e) {
    e.preventDefault();
    $('#render-ta').val(getRenderText(false));
    copyRenderTextToClipboard();
});

/**
 * get the rendered text of the form with wanted notation
 * @return {string|string}
 */
function getRenderText(isWikiNotation = false) {
    let renderorder = getRenderOrder();
    let renderText = "";

    // first part of the form element
    if ( isWikiNotation ) {
        renderText += formElement.getWikiStartText();
    } else {
        renderText += formElement.getWsFormStartText();
    }

    // body of the form
    for ( let $i in renderorder ) {
        let isHiddenField = false;
        for ( let $j in renderorder[$i] ) {
            if ( renderorder[$i][$j].type === 'wscreate' || renderorder[$i][$j].type === 'wsedit' ) {
                isHiddenField = true;
            }
        }

        if ( !isHiddenField ) {
            renderText += "\n\t<div class='row'>\n";
            for ( let $j in renderorder[$i] ) {
                renderText += "\t\t<div class='col-md-" + renderorder[$i][$j].getColNr() + "'>\n\t\t\t";

                if ( isWikiNotation ) {
                    renderText += renderorder[$i][$j].getWikiText();
                } else {
                    renderText += renderorder[$i][$j].getWsFormText();
                }

                renderText += "\n\t\t</div>\n";
            }
            renderText += "\t</div>\n";
        } else {
            for ( let $j in renderorder[$i] ) {
                if ( isWikiNotation ) {
                    renderText += "\n\t" + renderorder[$i][$j].getWikiText();
                } else {
                    renderText += "\n\t" + renderorder[$i][$j].getWsFormText();
                }
            }
        }

    }

    // last part of the form element
    if ( isWikiNotation ) {
        renderText += formElement.getWikiEndText();
    } else {
        renderText += formElement.getWsFormEndText();
    }
    //copyRenderTextToClipboard();
    return renderText;
}

/**
 * get object with order of the elements
 * @return {object}
 */
function getRenderOrder() {
    let rowsOrder = drop.toArray();
    let renderorder = {};
    let order = 0;
    $(rowsOrder).each(function (index, rowsDataId) {
        $(".editor-hidden").each(function (i, row) {
            let count = 0;
            if ( $(row).data('id') == rowsDataId ) {
                let childObj = {};
                $(row).children().each(function (j, child) {
                    if ( $(child).hasClass("ws-form-col") ) {
                        let elmObj = getElementFromId(child.id);
                        childObj[count] = elmObj;
                        count++;
                    }
                });
                order++;
                renderorder[order] = childObj;
            }
        });
    });
    $(rowsOrder).each(function (index, rowsDataId) {
       $('.editor-not-hidden').each(function (i, row) {
           let count = 0;
           if ( $(row).data('id') == rowsDataId ) {
               let childObj = {};
               $(row).children().each(function (j, child) {
                   if ( $(child).hasClass("ws-form-col") ) {
                       let elmObj = getElementFromId(child.id);
                       childObj[count] = elmObj;
                       count++;
                   }
               });
               order++;
               renderorder[order] = childObj;
           }
       });
    });
    return renderorder;
}

/**
 * on keypress function for the new-form-input
 * when hitting enter click add-new-form-btn
 */
$('#form-name-input').on('keypress', function(e) {
   if ( e.keyCode == 13 ) {
       $('#add-new-form-btn').click();
   }
});

/**
 * on keypress function for the input field in the save modal
 * hit enter and click the btn
 */
$('#file-name-input').on('keypress', function(e) {
    if ( e.keyCode == 13 ) {
        $('#save-form-btn').click();
    }
});

/**
 * @onclick -> creating new form
 */
$('#add-new-form-btn').click( function (e) {
    let form_name = $('#form-name-input').val();
    formElement = new FormElement("form", {});
    formElement.name = form_name;
    $('#form-name-header')[0].textContent = form_name;
    $('#file-name-input').val(form_name);
    if ( $('#ws-sortable').children().length > 0 ) {
        if ( confirm("Do you want to whipe out this form you've build?") ) {
            $('#ws-sortable').html("");
        }
    }
});


/**
 * @onclick -> save the form
 */
$('#save-form-btn').click(function (e) {
    if ( $('#file-name-input').val() !== "" && typeof formElement == 'object') {
        let filename = $('#file-name-input').val() + ".json";
        let renderorder = getRenderOrder();
        let formJson = {
            form: formElement
        };
        let data = {
            formName: filename,
            content: JSON.stringify(renderorder),
            formElement: JSON.stringify(formJson)
        };
        var path = mw.config.get('wgScriptPath');
        if( path === null || !path ) {
            path = '';
        }
        // /data/www/sg-staging.wikibase.nl/public_html/extensions/WSForm/formbuilder/php/saveform.php
        $.post( path + '/extensions/WSForm/formbuilder/php/saveform.php', data,
            function (what) {
                //console.log(what);
            } ,"json");
        showSuccessSaveAlert($('#file-name-input').val());
    } else if ( typeof formElement != 'object' ) {
        showErrorAlert('There is no form element defined! Click on new form to add one.');
    } else if ( $('#file-name-input').val() == "" ) {
        showErrorAlert('The file name is empty..');
    }
});

/**
 * @shortcut to save a form
 * shortcut : SHIFT + CTRL + S
 */
$(document).on('keypress', function(e) {
    if ( e.ctrlKey && e.shiftKey ) {
        if ( e.keyCode === 19 || e.key === 'S') {
            $('#save-form-btn').click();
        }
    }
});

/**
 * on click funtion for the start screen when hit load form -> loads all the files
 */
$('#load-from-start').on('click', function(e) {
    showAllFiles();
});

/**
 * show success save alert
 * @param msg
 */
function showSuccessSaveAlert(msg) {
    iziToast.show({
        color: '#47B815',
        position: "bottomRight",
        timeout: 3500,
        title: 'Saved!',
        message: 'file: ' + msg,
        pauseOnHover: false
    });
}

/**
 * show success alert
 * @param msg
 */
function showSuccessalert(msg) {
    iziToast.show({
        color: '#47b815',
        position: "center",
        timeout: 3000,
        title: 'OK!',
        message: msg,
        pauseOnHover: false
    });
}

/**
 * show error alert
 * @param msg
 */
function showErrorAlert(msg) {
    iziToast.show({
        color: '#d93419',
        position: 'center',
        timeout: 7000,
        title: 'Error!',
        message: msg,
        pauseOnHover: false
    });
}