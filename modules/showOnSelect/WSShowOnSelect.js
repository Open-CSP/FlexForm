/**
 * applying show on select on the page and make sure everyting will be handled as needed
 */
function WsShowOnSelect() {
    var selectArray = [];
    $('.WSShowOnSelect').find('[data-wssos-show]').each(function (index, elm) {
        if ( $(elm).is('option') ) {
            var isInArray = false;
            var selectParent = $(elm).parent()[0];
            for ( var i = 0; i < selectArray.length; i++ ) {
                if ( $(selectParent).is($(selectArray[i])) ) {
                    isInArray = true;
                }
            }
            if ( !isInArray ) {
                selectArray.push(selectParent);
                handleSelect(selectParent);
            }
        } else if ( $(elm).is('input[type=radio]') ) {
            handleRadio(elm);
        } else if ( $(elm).is('input[type=checkbox]') ) {
            handleCheckbox(elm);
        } else if ( $(elm).is('button') ) {
            handleButton(elm);
        }
    });
}

/**
 * handle the radio button changes, show what is needed
 * @param radioElm
 */
function handleRadio(radioElm) {
    var pre_wssos_value = $(radioElm).data('wssos-show');
    var pre_parent_wssos = $(radioElm).parentsUntil('.WSShowOnSelect').parent()[0];
    var pre_wssos_elm = $(pre_parent_wssos).find('[data-wssos-value="'+pre_wssos_value+'"]');
    if ( $(radioElm).parent().hasClass('WSShowOnSelect') ) {
        pre_parent_wssos = $(radioElm).parent()[0];
        pre_wssos_elm = $(pre_parent_wssos).find('[data-wssos-value="'+pre_wssos_value+'"]');
    }
    if ( radioElm.checked ) {
        $(pre_wssos_elm).removeClass('hidden');
        putAllTypesDataInName(pre_wssos_elm);
    } else {
        putAllTypesNameInData(pre_wssos_elm);
    }
    $(pre_parent_wssos).find('input[type=radio][name="'+ radioElm.name +'"]').on('change', function () {
       var wssos_value = $(this).data('wssos-show');
       var parent_wssos = $(this).parentsUntil('.WSShowOnSelect').parent()[0];
        var wssos_elm = $(parent_wssos).find('[data-wssos-value="'+wssos_value+'"]');
        if ( $(this).parent().hasClass('WSShowOnSelect') ) {
            parent_wssos = $(this).parent()[0];
            wssos_elm = $(parent_wssos).find('[data-wssos-value="'+wssos_value+'"]');
        }
       $(parent_wssos).find('input[name="'+this.name+'"][type="radio"]').each(function(index, radiobtn) {
          var radio_hide_data_attr = $(radiobtn).data('wssos-show');
           $(parent_wssos).find('[data-wssos-value="'+radio_hide_data_attr+'"]').addClass('hidden');
           putAllTypesNameInData($(parent_wssos).find('[data-wssos-value="'+radio_hide_data_attr+'"]'));
       });

       if ( this.checked ) {
           wssos_elm.removeClass('hidden');
           putAllTypesDataInName(wssos_elm);
       } else {
           wssos_elm.addClass('hidden');
           putAllTypesNameInData(wssos_elm);
       }
    });
}

/**
 * handle the checkbox changes, show what is needed
 * @param checkElm
 */
function handleCheckbox(checkElm) {
    var pre_wssos_value = $(checkElm).data('wssos-show');
    var pre_parent_wssos = $(checkElm).parentsUntil('.WSShowOnSelect').parent()[0];
    var pre_wssos_elm = $(pre_parent_wssos).find('[data-wssos-value="'+pre_wssos_value+'"]');
    if ( $(checkElm).parent().hasClass('WSShowOnSelect') ) {
        pre_parent_wssos = $(checkElm).parent()[0];
        pre_wssos_elm = $(pre_parent_wssos).find('[data-wssos-value="'+pre_wssos_value+'"]');
    }
    if ( checkElm.checked ) {
        pre_wssos_elm.removeClass('hidden');
        // set the dataset value of data-name-attribute back in the name attribute
        putAllTypesDataInName(pre_wssos_elm);

        // set the name value of the unchecked element in the value of data-name-attribute and remove the name attribute
        if ( $(checkElm).has('data-wssos-show-unchecked') ) {
            var pre_unchecked_value = $(checkElm).data('wssos-show-unchecked');
            var pre_unchecked_elm = $(pre_parent_wssos).find('[data-wssos-value="'+pre_unchecked_value+'"]');
            putAllTypesNameInData(pre_unchecked_elm);
        }
    } else {
        // set data-name-attribute to the value of name attribute and remove the name attribute
        putAllTypesNameInData(pre_wssos_elm);

        if ( $(checkElm).has('data-wssos-show-unchecked') ) {
            var pre_unchecked_value = $(checkElm).data('wssos-show-unchecked');
            var pre_unchecked_elm = $(pre_parent_wssos).find('[data-wssos-value="'+pre_unchecked_value+'"]');
            $(pre_unchecked_elm).removeClass('hidden');
            // set the name attribute to the value of data-name-attribute
            putAllTypesDataInName(pre_unchecked_elm);
        }
    }
    $(checkElm).on('change', function(e) {
        e.stopPropagation();
       var wssos_value = $(this).data('wssos-show');
       var parent_wssos = $(this).parentsUntil('.WSShowOnSelect').parent()[0];
       var wssos_elm = $(parent_wssos).find('[data-wssos-value="'+wssos_value+'"]');
        if ( $(this).parent().hasClass('WSShowOnSelect') ) {
            parent_wssos = $(this).parent()[0];
            wssos_elm = $(parent_wssos).find('[data-wssos-value="'+wssos_value+'"]');
        }
        if ( this.checked ) {
            wssos_elm.removeClass('hidden');
            putAllTypesDataInName(wssos_elm);
        } else {
            wssos_elm.addClass('hidden');
            putAllTypesNameInData(wssos_elm);
        }

        if ( $(this).has('data-wssos-show-unchecked') ) {
            var wssos_unchecked_value = $(this).data('wssos-show-unchecked');
            var wssos_unchecked_elm = $(parent_wssos).find('[data-wssos-value="'+wssos_unchecked_value+'"]');
            if ( this.checked ) {
                wssos_unchecked_elm.addClass('hidden');
                putAllTypesNameInData(wssos_unchecked_elm);
            } else {
                wssos_unchecked_elm.removeClass('hidden');
                putAllTypesDataInName(wssos_unchecked_elm);
            }
        }
    });
}


/**
 * handle the select box changes to show what is needed on select
 * @param selectElm
 */
function handleSelect(selectElm) {
    var selectVal = $(selectElm).val();
    $(selectElm).children().each(function (index, option) {
        var wssos_value = $(option).data('wssos-show');
        var parent_wssos = $(option).parentsUntil('.WSShowOnSelect').parent()[0];
        var wssos_elm = $(parent_wssos).find('[data-wssos-value="'+wssos_value+'"]');
        if ( option.selected || $(option).val() == selectVal) {
            wssos_elm.removeClass('hidden');
            putAllTypesDataInName(wssos_elm);
        } else {
            wssos_elm.addClass('hidden');
            putAllTypesNameInData(wssos_elm);
        }
    });

    $(selectElm).on('change', function () {
        $(this).children().each(function (index, option) {
            var wssos_value = $(option).data('wssos-show');
            var parent_wssos = $(this).parentsUntil('.WSShowOnSelect').parent()[0];
            var wssos_elm = $(parent_wssos).find('[data-wssos-value="'+wssos_value+'"]');
            if ( option.selected ) {
                wssos_elm.removeClass('hidden');
                putAllTypesDataInName(wssos_elm);
            } else {
                wssos_elm.addClass('hidden');
                putAllTypesNameInData(wssos_elm);
            }
        });
    });
}

function handleButton(btnElm) {
    var pre_wssos_value = $(this).data('wssos-show');
    var pre_parent_wssos = $(this).parentsUntil('.WSShowOnSelect').parent()[0];
    var pre_wssos_elm = $(pre_parent_wssos).find('[data-wssos-value="'+pre_wssos_value+'"]');

    // set up the start and make sure the element is hidden
    $(pre_wssos_elm).addClass('hidden');
    putAllTypesNameInData(pre_wssos_elm);
    // add on click listener to the button
    $(btnElm).on('click', function(e) {
        var wssos_value = $(this).data('wssos-show');
        var parent_wssos = $(this).parentsUntil('.WSShowOnSelect').parent()[0];
        var wssos_elm = $(parent_wssos).find('[data-wssos-value="'+wssos_value+'"]');

        // possibility to hide the wanted element back if an option
        if ( !$(wssos_elm).hasClass('hidden') ) {
            $(wssos_elm).addClass('hidden');
            putAllTypesNameInData(wssos_elm);
        } else {
            $(wssos_elm).removeClass('hidden');
            putAllTypesDataInName(wssos_elm);
        }
    });
}

/**
 * find all different types which name attribute should go to the dataset
 * @param elm
 */
function putAllTypesNameInData(elm) {
    putNameAttrValueInDataset($(elm).find('input,select,textarea'));
    putRequiredInDataset($(elm).find('input,select,textarea'));
}


/**
 * find all different types which data-attribute should go to the name-attribute
 * @param elm
 */
function putAllTypesDataInName(elm) {
    putDatasetValueBackInName($(elm).find('input,select,textarea'));
    putDatasetInRequired($(elm).find('input,select,textarea'));
}

/**
 * set the name attribute value to the dataset data-name-attribute, remove the name attribute
 * @param elm
 */
function putNameAttrValueInDataset($elm) {
    $.each($elm, function (index, elm) {
        if ( $(elm).attr('name') !== '' ) {
            var name = $(elm).attr('name');
            if (name) {
                $(elm).attr('data-name-attribute', name);
                $(elm).removeAttr('name');
            }
        }
    });
}


/**
 * set the name attribute to the value of the data-name-attribute
 * @param elm
 */
function putDatasetValueBackInName($elm) {
    $.each($elm, function(index, elm) {
        if ( $(elm).attr('data-name-attribute') !== '' ) {
            var datasetName = $(elm).data('name-attribute');
            if (datasetName) {
                $(elm).attr('name', datasetName);
            }
        }
    });
}

/**
 * set the required attr in the dataset data-ws-required
 * @param $elm
 */
function putRequiredInDataset($elm) {
    $.each($elm, function (index, elm) {
        if ( $(elm).is(':required') ) {
            $(elm).attr('data-ws-required', true);
            $(elm).prop('required', false);
        }
    });
}

/**
 * if the element has data-ws-required the make the element required
 * @param $elm
 */
function putDatasetInRequired($elm) {
    $.each($elm, function (index, elm) {
        if ( $(elm).data('ws-required') ) {
            $(elm).prop('required', true);
        }
    })
}


