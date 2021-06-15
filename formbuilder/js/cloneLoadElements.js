/**
 * create clone element
 * @return {HTMLDivElement}
 */
function createCloneElement(dataId) {
    let div = document.createElement("div");
    let i = document.createElement("i");
    $(i).addClass("fa");
    $(i).addClass("fa-clone");
    $(div).on('click', function (e) {
        let parent = $('div['+dataId+']');
        cloneRowIntoList(parent);
    });
    $(div).addClass('clone-element');
    $(div).append(i);
    return div;
}

/**
 * clones the selected row into the list
 * @param row
 */
function cloneRowIntoList(row) {
    let d = new Date();
    let newDataId = "ws-form-row-" + d.getTime();
    let clone = $(row).clone();
    let children = $(row).children();
    $(clone)[0].dataset["id"] = newDataId;
    $(clone).html("");
    let childrenCloneArray = [];
    let count = 0;
    $(children).each(function (i, child) {
        if ( $(child).hasClass('ws-form-col') ) {
            let element = getElementFromId(child.id);
            let type = element.type;
            let attr = element.attr;
            let cols = element.cols;
            let dataAttr = element.dataAttr;
            let newElm = addElement(type);

            if ( newElm.type === "select" ) {
                newElm.setOptions(element.options);
                newElm.countOptions = element.countOptions;
            }

            newElm.setAttr(attr);
            newElm.setDataAttr(dataAttr);

            newElm.id = newElm.type + "_" + (d.getTime() + count);
            newElm.setCols(cols);
            $(clone).append(newElm.getElement());
            childrenCloneArray.push(newElm);
            count += 5;
        }
    });

    $(clone).append(createCloneElement('data-id="'+newDataId+'"'));
    $("#ws-sortable").append(clone);
    setNestedSortable();
    setUpEditable();
}


/**
 * load the form in to the page
 * @param filename of the form
 */
function loadForm(filename) {
    let data = {
        filename: filename
    };
    var path = mw.config.get('wgScriptPath');
    if( path === null || !path ) {
        path = '';
    }
    $.post( path + '/extensions/WSForm/formbuilder/php/loadForm.php', data,
        function (json) {
            convertJSONToForm(json);
        } ,"json");
}

function convertJSONToForm(json) {
    $('#ws-sortable').html("");
    // check if the hidden fields tab is active
    /*$('.hidden-fields').each(function() {
        if ( $('.show-hide-hidden-fields').text() === 'Hide' ) {
            $(this).click();
        }
    });*/
    if ( $('#btn-toggle-hidden-fields').text() === 'Hide hidden fields' ) {
        $('#btn-toggle-hidden-fields').click();
    }
    let content = JSON.parse(json['content']);
    let filename = json['filename'].slice(0, json['filename'].indexOf("."));
    if ( typeof json['formElement'] != 'undefined' ) {
        let formJson = JSON.parse(json['formElement']);
        let formObj = formJson['form'];
        $('#form-name-header')[0].textContent = formObj['name'];
        formElement = new FormElement('form', formObj['attr']);
        formElement.setDataAttr(formObj['dataAttr']);
        formElement.name = formObj['name'];
        $('#file-name-input').val(formObj['name']);
    } else {
        formElement = new FormElement('form', {});
        formElement.name = filename;
        $('#form-name-header')[0].textContent = filename;
        $('#file-name-input').val(filename);
    }

    let d = new Date();
    let count = 0;
    for ( let $i in content ) {
        let rowDataId = "ws-form-row-" + (d.getTime() + count);
        let rowElement = createWsFormRow(rowDataId);
        for ( let $index in content[$i] ) {
            let elmObj = content[$i][$index];
            let type = elmObj['type'];
            let attr = elmObj['attr'];
            let cols = elmObj['cols'];
            let dataAttr = elmObj['dataAttr'];
            let newElm = addElement(type);

            if ( type === 'wscreate' || type === 'wsedit' ) {
                $(rowElement).addClass('editor-hidden');
                $(rowElement).addClass('hidden');
            } else {
                $(rowElement).addClass('editor-not-hidden');
            }

            if ( newElm.type == 'select' ) {
                newElm.setOptions(elmObj['options']);
            }
            newElm.setAttr(attr);
            newElm.setDataAttr(dataAttr);
            newElm.setCols(cols);
            newElm.id = type + "_" + (d.getTime() + count);

            $(rowElement).append(newElm.getElement());
            count += 5;
        }
        $(rowElement).append(createCloneElement('data-id="'+rowDataId+'"'));
        $('#ws-sortable').append(rowElement);
    }
    setNestedSortable();
    setUpEditable();
}

function removeFile(filename) {
    let data = {
        filename: filename
    };
    var path = mw.config.get('wgScriptPath');
    if( path === null || !path ) {
        path = '';
    }
    $.post(path + '/extensions/WSForm/formbuilder/php/removeForm.php', data, function(res) {

    }, "json");
}

/**
 * open modal to choose from already created forms
 */
$('#load-form-btn').on('click', function (e) {
    e.preventDefault();
    showAllFiles();
});


/**
 * shows all the files available
 */
function showAllFiles() {
    $('#search-form-input').val("");
    var path = mw.config.get('wgScriptPath');
    if( path === null || !path ) {
        path = '';
    }
    $.post( path + '/extensions/WSForm/formbuilder/php/getAllFiles.php', {},
        function (data) {
            filesArray = [];
            let count = 0;
            $('#load-files').html(createParentTable());
            for ( let $i in data) {
                let d = new Date();
                let loadElementId = "load-element-" + (d.getTime() + count);
                data[$i]["element-id"] = loadElementId;
                //$('#load-files').append(createLoadElement(data[$i]));
                $('#load-files-tbody').append(createLoadRow(data[$i]));
                filesArray.push(data[$i]);
                count += 5;
            }
        }, 'json');
}

/**
 * generates a link to share
 * @param obj
 */
function generateLink(obj) {
    let server = obj.server;
    let langPath = obj.pathinfo.realfile;
    let shortpath = langPath.slice(langPath.indexOf('/extensions'));

    let link = 'https://' + server + shortpath;
    return link;
}

/**
 * Create the default table element
 * @returns {string}
 */
function createParentTable() {
    return `<table class="table table-hover">
  <thead>
    <tr>
      <th scope="col">File name</th>
      <th scope="col">Last updated by</th>
      <th scope="col">Last updated</th>
      <th scope="col">#</th>
    </tr>
  </thead>
  <tbody id="load-files-tbody">
   </tbody>
</table>`;
}

/**
 *
 * @param fileObj
 * @return {HTMLDivElement}
 */
function createLoadElement(fileObj) {
    let div = document.createElement("div");
    let btn = document.createElement("a");
    let i = document.createElement("i");
    let delBtn = document.createElement("button");
    div.id = fileObj['element-id'];
    div.style = "height:50px; widht:80%; border:1px solid #ddd; border-radius: 5px; padding-left: 10px; padding-right: 10px; padding-top: 5px; padding-bottom: 5px;text-align:center;";
    $(btn).addClass("btn");
    $(btn).addClass("btn-info");
    delBtn.style = "background:darkred!important;border-radius:5px;color:#fff;float:right;width:auto!important;";
    delBtn.dataset['delete_id'] = fileObj['filename'];
    delBtn.textContent = 'x';
    btn.style = "background:#cdcdcd!important; float:left!important;";
    btn.textContent = fileObj['filename'];
    i.textContent = fileObj['lastModifiedBy'] + " - " + fileObj['lastModifiedDate'];
    i.style = 'float:center;';
    $(btn).attr('data-izimodal-close', "");
    $(btn).on('click', function (e) {
        loadForm(this.textContent+".json");
    });
    $(delBtn).on('click', function (e) {
        let file_name = $(this).data('delete_id');
        if ( confirm("Are you sure you want to remove '" + file_name + "'") ) {
            removeFile(file_name+".json");
            $(this).parent().remove();
        }
    });
    $(div).append(btn);
    $(div).append(i);
    $(div).append(delBtn);
    return div;
}

/**
 * creates for each file from the server a table row element
 * @param fileObj
 * @returns {HTMLTableRowElement}
 */
function createLoadRow(fileObj) {
    let fileName = fileObj['filename'];
    let lastModifiedBy = fileObj['lastModifiedBy'];
    let lastModifiedDate = fileObj['lastModifiedDate'];
    let tempId = fileObj['element-id'];
    let openBtn = document.createElement("button");
    let delBtn = document.createElement("button");
    let buttonTd = document.createElement("td");
    let shareBtn = document.createElement("button");
    shareBtn.textContent = 'share';
    $(shareBtn).addClass('btn');
    $(shareBtn).addClass('btn-light');
    $(shareBtn).css({
        "background": "#00a2ff",
        "width": "fit-content"
    });
    $(openBtn).addClass('btn');
    $(openBtn).addClass('btn-success');
    $(openBtn).css({
        "background-color": "#a5bc00",
        "margin-left": "3px",
        "margin-right": "3px",
        "width": "fit-content"
    });
    $(delBtn).addClass('btn');
    $(delBtn).addClass('btn-danger');
    $(delBtn).css({
        "background-color": 'red',
        "width": "fit-content"
    });
    openBtn.textContent = 'open';
    delBtn.textContent = 'x';
    delBtn.dataset['delete_id'] = fileName;
    openBtn.dataset['open_id'] = fileName;
    shareBtn.dataset['share_id'] = fileName;
    $(openBtn).attr('data-izimodal-close', "");

    $(openBtn).on('click', function (e) {
        loadForm($(this).data('open_id')+".json");
    });

    $(delBtn).on('click', function (e) {
        let file_name = $(this).data('delete_id');
        if ( confirm("Are you sure you want to remove '" + file_name + "'") ) {
            removeFile(file_name+".json");
            $(this).parent().remove();
        }
    });

    $(shareBtn).on('click', function (e) {
        var path = mw.config.get('wgScriptPath');
        if( path === null || !path ) {
            path = '';
        }
        $.post( path + '/extensions/WSForm/formbuilder/php/loadForm.php', {filename: $(this).data('share_id')+".json"},
            function (json) {
                let data = JSON.stringify(json);
                $.ajax({
                    url: "https://extendsclass.com/api/json-storage/bin",
                    type: "POST",
                    data: data,
                    contentType: "application/json; charset=utf-8",
                    dataType: "json",
                    success: function (data, textStatus, jqXHR) {
                        let link = data['uri'];

                        // show toast with link
                        iziToast.info({
                            timeout: 20000,
                            overlay: true,
                            displayMode: 'once',
                            id: 'share',
                            zindex: 999,
                            title: 'Share',
                            message: 'Link:',
                            position: 'center',
                            drag: false,
                            inputs: [
                                ['<input id="copyLinkInput" type="text" value="'+ link + '">', 'keyup', function (instance, toast, input, e) {}, true]
                            ],
                            buttons: [
                                ['<button id="copyLinkBtn">Copy</button>', (instance ,toast) => {

                                }, true]
                            ]
                        });

                        // handle copy
                        let id = '#copyLinkInput';
                        copyLinkFromInput(id);
                        $('#copyLinkBtn').click((e) => {
                            copyLinkFromInput(id);
                        });

                    }
                });
            } ,"json");

    });
    let parent = document.createElement("tr");
    let fileElm = document.createElement('th');
    let byElm = document.createElement('td');
    let dateElm = document.createElement( 'td');
    parent.id = tempId;
    fileElm.textContent = fileName;
    fileElm.scope = "row";
    byElm.textContent = lastModifiedBy;
    dateElm.textContent = lastModifiedDate;

    $(parent).append(fileElm);
    $(parent).append(byElm);
    $(parent).append(dateElm);

    $(buttonTd).append(delBtn);
    $(buttonTd).append(openBtn);
    $(buttonTd).append(shareBtn);
    $(parent).append(buttonTd);

    return parent;
}


function copyLinkFromInput(id) {
    $(id).focus();
    $(id).select();
    document.execCommand('copy');
}

/**
 * creates the row for in the list
 * @param dataId
 * @return {HTMLElement}
 */
function createWsFormRow(dataId) {
    let div = document.createElement("DIV");
    div.dataset["id"] = dataId;
    $(div).addClass("row");
    $(div).addClass("form-row");
    $(div).addClass("ws-form-row");
    return div;
}

/**
 * @oninput search field in the load form modal
 * display only the filenames / last modified date and user which includes the search value
 */
$('#search-form-input').on('input', function() {
    let searchvalue = $(this).val();
    for ( let i = 0; i < filesArray.length; i++ ) {
        let fileObj = filesArray[i];
        if ( searchvalue === "" ) {
            $('#'+fileObj['element-id']).removeClass('hidden');
        } else {
            if (checkIfObjContainsValue(fileObj, searchvalue)) {
                $('#' + fileObj['element-id']).removeClass("hidden");
            } else {
                $('#' + fileObj['element-id']).addClass('hidden');
            }
        }
    }
});


/**
 * check search value is in filename or lastModifiedBy or lastModifiedDate
 * @param obj
 * @param val
 * @return {boolean}
 */
function checkIfObjContainsValue(obj, val) {
    if (obj['filename'].toLowerCase().includes(val.toLowerCase())) {
        return true;
    }
    if ( obj['lastModifiedBy'].toLowerCase().includes(val.toLowerCase())) {
        return true;
    }
    if ( obj['lastModifiedDate'].includes(val) ) {
        return true;
    }
    return false;
}


$('#open-url-form').on('click', function(e) {
   let form_link = $('#url-form').val();
    $.get(form_link, function (data, textStatus, jqHXR) {
            convertJSONToForm(data);
        }, 'json');
});
