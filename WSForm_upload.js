/*!
 * WSForm default file handling
 * Author : Sen-Sai
 */


/**
 * @brief Default file handling
 * This function is used when file upload is included in WSForm
 * @param id id of the form
 * @param verbose_id id of the element where the verbose information will end up in
 * @param error_id id of the element where error will end ip in
 * @param hide the actual file upload button
 * @param verbose_custom
 * @param error_custom
 */
function wsfiles(id, verbose_id, error_id, hide, verbose_custom, error_custom) {
    //alert ('changed');
    var idfile = $('#' + id);
    var form = idfile.closest('form');
    var enctype = form.attr('enctype');
    //  var convert_to = idfile.attr('data-force');
    var current_files = idfile[0].files;
    var verbose = $('#' + verbose_id);
    var error = $('#' + error_id);
    if (verbose_custom === "none") {
        verbose_custom = false;
    } else {
        verbose_custom = true;
    }
    if (error_custom === "none") {
        error_custom = false;
    } else {
        error_custom = true;
    }

    /*
        if(typeof convert_to === 'undefined' || !convert_to || !isImage(convert_to)) {
          convert_to = false;
          alert("force parameter ("+convert_to+")not valid");
        }
    */
    if ((typeof enctype === 'undefined') || enctype === false || enctype
        !== 'multipart/form-data' ) {
        var er = '<ol><li>You cannot upload a file without a form enctype parameter.';
        er += '<br>(<a target="_blank" href="https://developer.mozilla.org/en-US/docs/Web/HTML/Element/form">';
        er += 'https://developer.mozilla.org/en-US/docs/Web/HTML/Element/form</a>)<BR>';
        er += '[hint: enctype should be multipart/form-data]</li></ol>';
        error.html(er);
        $('#' + id).attr("disabled", "disabled");
        return;
    }
    if (hide) {
        $(idfile).css('opacity', 0);
    }
    if (current_files.length === 0) {
        verbose.html('No file selected for upload');
    } else {
        var output = '<ol>';
        var err = '<ol>';
        if (verbose_custom) {
            output = '';
        }
        if (error_custom) {
            err = '';
        }
        for (var i = 0; i < current_files.length; i++) {
            if (validFileType(idfile, current_files[i])) {
                console.log(current_files[i]);
                if (isImage(current_files[i].type)) {
                    /*   VOOR LATER
                      if( current_files[i].type !== convert_to ) {
                        if(convert_to !== false) {
                          var cannie = convertImageToCanvas($('#' + id).val());
                          $('#' + id).val( convertCanvasToImage( cannie, convert_to ) );
                        }
                      }
                      */
                    if (!verbose_custom) {
                        var del = ' <i class="fa fa-times wsform-reset-button" onClick="resetFile(\'' + id + '\', \'' + verbose_id + '\' )"></i> ';
                        output += '<li>' + del;
                        var src = window.URL.createObjectURL(current_files[i]);
                        output += '<img class="wsform-image-preview" src="' + src + '">';
                        output += 'File name ' + current_files[i].name + '.</li>';
                    } else {
                        var del = '<i class="fa fa-times wsform-reset-button" onClick="resetFile(\'' + id + '\', \'' + verbose_id + '\' )"></i> ';
                        output += '<li>' + del;
                        var src = window.URL.createObjectURL(current_files[i]);
                        output += '<img class="wsform-image-preview" src="' + src + '">';
                        output += 'File name ' + current_files[i].name + '.</li>';
                    }
                } else {
                    output += '<li>File name ' + current_files[i].name + '.</li>';
                }
            } else {
                if (!error_custom) {
                    err += '<li>File name ' + current_files[i].name + ': Not a valid file type. Update your selection.</li>';
                    $('#' + id).val("");
                } else {
                    err += '<li>File name ' + current_files[i].name + ': Not a valid file type. Update your selection.</li>';
                    $('#' + id).val("");
                }
            }
        }
        output += '</ol>';
        if (err
            !== '<ol>') {
            err += '</ol>';
        } else err = '';

        verbose.html(output);
        error.html(err);

    }
}

function convertImageToCanvas(image) {
    var oCanvas = document.createElement("canvas");
    oCtx = oCanvas.getContext('2d');
    oCanvas.width = image.width;
    oCanvas.height = image.height;
    oCtx.drawImage(image, 0, 0);

    return oCanvas;
}

function convertCanvasToImage(canvas, type) {
    var image = new Image();
    image.src = canvas.toDataURL(type);
    return image;
}

function getFileNameOnly(file) {
    var filename = file.substring(0, file.lastIndexOf('.'));
    return filename;
}

function resetFile(id, verbose_id) {
    $('#' + id).val("");
    $('#' + verbose_id).html('No file selected for upload');
}

function validFileType(idfile, file) {
    if (idfile.attr('accept')) {

        var allowed = idfile.attr('accept').split(',');
        var set = false;
        for (var i = 0; i < allowed.length; i++) {
            var tt = getFileType($.trim(allowed[i]));
            //alert(file.type + ' === ' + tt);
            if (file.type === tt) {
                set = true;
            }

        }
        return set;
    } else return true;
}

function isImage(type) {
    var fileTypes = [
        'image/jpeg',
        'image/pjpeg',
        'image/png'
    ];
    for (var i = 0; i < fileTypes.length; i++) {
        if (type === fileTypes[i]) {
            return true;
        }
    }
    return false;
}

function getFileType(ffile) {
    var extension = ffile.substr((ffile.lastIndexOf('.') + 1));

    switch (extension) {
        case 'jpg' :
        case 'jpeg' :
            return 'image/jpeg';
            break;
        case 'png' :
            return 'image/png';
            break;
    }
}
