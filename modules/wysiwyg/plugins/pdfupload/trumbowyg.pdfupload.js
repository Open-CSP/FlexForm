(function ($) {
    'use strict';

    var defaultOptions = {
        serverPath: '/api-wb.php',
        fileFieldName: 'fileToUpload',
        data: [],                       // Additional data for ajax [{name: 'key', value: 'value'}]
        headers: {},                    // Additional headers
        xhrFields: {},                  // Additional fields
        urlPropertyName: 'file',        // How to get url from the json response (for instance 'url' for {url: ....})
        statusPropertyName: 'success',  // How to get status from the json response
        success: undefined,             // Success callback: function (data, trumbowyg, $modal, values) {}
        error: undefined                // Error callback: function () {}
    };
/*
    function getDeep(object, propertyParts) {
        var mainProperty = propertyParts.shift(),
            otherProperties = propertyParts;

        if (object !== null) {
            if (otherProperties.length === 0) {
                return object[mainProperty];
            }

            if (typeof object === 'object') {
                return getDeep(object[mainProperty], otherProperties);
            }
        }
        return object;
    }
*/
    function returnLastPartOfFileName(val) {
      return val.split('/').slice(-1)[0];
    }

    function createDivObjectElement(file, file_description, old_file_name, new_file_name, data_attribute, data_name, files, filenames_input, formID) {
      var div_input_object = document.createElement('DIV'),
          hidden_desc_input = document.createElement('input'),
          hidden_filename_input = document.createElement('input'),
          object_file = document.createElement('object'),
          file_input = files[0],
          div_object = document.createElement('DIV'),
          div_desc = document.createElement('DIV'),
          html_bold_tag = document.createElement('B'),
          del_btn = document.createElement('BUTTON');


      file_input.classList.add('hidden', 'wsattaching__file-hidden');
      file_input.name = "wysiwyg_uploadfiles[]";
      hidden_desc_input.setAttribute('type', 'hidden');
      hidden_desc_input.name = "wysiwyg_description[]";
      hidden_desc_input.value = old_file_name + '||' + file_description;
      hidden_desc_input.classList.add('wsattaching__file-hidden');
      hidden_filename_input.setAttribute('type', 'hidden');
      hidden_filename_input.name = "wysiwyg_filenames[]";
      hidden_filename_input.value = old_file_name + '||' + new_file_name;
      hidden_filename_input.classList.add('wsattaching__file-hidden');
      object_file.setAttribute('data', data_attribute);

      html_bold_tag.textContent = file_description;
      div_desc.classList.add('wsattaching__description');
      div_object.classList.add('wsattaching__preview');
      del_btn.classList.add('wsattaching__dismiss')
      del_btn.textContent = 'X';
      del_btn.getAttribute('onclick');
      del_btn.onclick = function (e) {
        e.preventDefault();
        var show_div = document.getElementById('show-attachments-'+formID);
        show_div.removeChild(div_input_object);
        var filenames_array = filenames_input.value.split(',');
        for ( var i = 0; i < filenames_array.length; i++ ) {
          if ( filenames_array[i] == new_file_name ) {
            filenames_array.splice(i, 1);
          }
        }
        filenames_input.value = filenames_array.join(',');
      }

      div_input_object.classList.add('wsattaching__item');
      div_input_object.appendChild(file_input);
      div_object.appendChild(object_file);
      div_input_object.appendChild(hidden_filename_input);
      div_input_object.appendChild(hidden_desc_input);
      div_input_object.appendChild(del_btn);
      div_desc.appendChild(html_bold_tag);
      div_input_object.appendChild(div_object);
      div_input_object.appendChild(div_desc);
      return div_input_object;
    }
    //addXhrProgressEvent();


    $.extend(true, $.trumbowyg, {
        langs: {
            // jshint camelcase:false
            en: {
                pdfupload: 'pdfupload',
                file: 'File',
                pdfuploadError: 'Error'
            }
        },
        // jshint camelcase:true

        plugins: {
            pdfupload: {
                init: function (trumbowyg) {
                    trumbowyg.o.plugins.pdfupload = $.extend(true, {}, defaultOptions, trumbowyg.o.plugins.pdfupload || {});
                    var btnDef = {
                        fn: function () {
                            trumbowyg.saveRange();

                            var file,
                                prefix = trumbowyg.o.prefix,
                                data_attribute,
                                tempFile,
                                //hahaha = ['png', 'gif', 'jpg', 'jpeg', 'doc', 'docx', 'xls', 'xlsx', 'pdf', 'zip', 'svg', 'mp4'],
                                //haha = mw.config.get('wgFileExtensions'),
                                reader  = new FileReader();

                                //console.log(haha);
                                reader.addEventListener("load", function () {
                                  data_attribute = reader.result;
                                }, false);

                            var $modal = trumbowyg.openModalInsert(
                                // Title
                                trumbowyg.lang.pdfupload,

                                // Fields
                                {
                                    upload: {
                                        type: 'file',
                                        required: true,
                                        attributes: {
                                          //accept: 'application/*'
                                          accept: '.png, .gif, .jpg, .jpeg, .doc, .docx, .xls, .xlsx, .pdf, .zip, .svg, .mp4'
                                        }
                                    },
                                    alt: {
                                        label: 'description',
                                        value: trumbowyg.getRangeText()
                                    }
                                },

                                // Callback
                                function (values) {
                                    //data.wysisygaction = 'savetowiki';
                                    var RegEx = ['[',']','{', '}', '|', '#', '<', '>', '%', '+', '?', '.', ','];
                                    var regex = /[{}|#<>%+?][]/g;
                                    var tempindex = file.name.lastIndexOf('.');
                                    var tfile = file.name.slice(0, tempindex);
                                    var validFilename = true;
                                    var errorArray = [];
                                    for ( var i = 0; i < RegEx.length; i++ ) {
                                      if ( tfile.indexOf(RegEx[i]) !== -1 ) {
                                        validFilename = false;
                                        errorArray.push(RegEx[i]);
                                      }
                                    }
                                    if ( !validFilename ) {
                                      var errorString = errorArray.join(" ; ");
                                      alert("The filename isn't allowed, remove these characters: " + errorString);
                                    }
                                    else {
                                      if ( file ) {
                                        reader.readAsDataURL(file);
                                      }

                                      /*
                                      var div_input_object = document.createElement('DIV'),
                                          file_input = document.createElement('input'),
                                          hidden_desc_input = document.createElement('input'),
                                          hidden_filename_input = document.createElement('input'),
                                          object_file = document.createElement('object'),
                                          del_btn = document.createElement('BUTTON'),
                                      */
                                      var pageId = mw.config.get( 'wgArticleId' ),
                                          d = new Date(),
                                          file_description = values.alt,
                                          old_file_name = file.name,
                                          test$form = $($modal).parents(),
                                          formid,
                                          attachments_input,
                                          attachments_div,
                                          selectedAttachments = [],
                                          new_file_name = pageId + '/' + d.getTime() + '/' + file.name;

                                      for( var i = 0; i < test$form.length; i++ ) {
                                        if ( test$form[i].tagName == 'FORM' ) {
                                          formid = test$form[i].id;
                                        }
                                      }

                                      if ( formid ) {
                                        attachments_div = document.getElementById('show-attachments-'+formid);
                                        attachments_input = document.getElementById('attachments-'+formid);
                                        selectedAttachments = attachments_input.value.split(',');
                                        var testing = true;
                                        for ( var i = 0; i < selectedAttachments.length; i++ ) {
                                          if ( returnLastPartOfFileName(selectedAttachments[i]) == old_file_name ) {
                                            if ( confirm('You already uploaded this file, do you want to upload it again?') ) {
                                              testing = true;
                                            }
                                            else {
                                              testing = false;
                                            }
                                            break;
                                          }
                                        }
                                        if ( testing ) {
                                          if ( selectedAttachments.length >= 5 ) {
                                            alert('The maximum is 5 attachments per comment');
                                          } else {
                                            if ( attachments_input.value == "" ) {
                                              attachments_input.value = new_file_name;
                                              selectedAttachments.push(new_file_name);
                                            } else {
                                              attachments_input.value += ','+new_file_name;
                                              selectedAttachments = attachments_input.value.split(',');
                                            }
                                            var element = createDivObjectElement(file, file_description, old_file_name, new_file_name, data_attribute, values.upload, tempFile, attachments_input, formid);
                                            attachments_div.appendChild(element);
                                          }
                                        }


                                      }

                                    /*
                                    file_input.setAttribute('type', 'file');
                                    file_input.style = "display: none;";
                                    file_input.value = file;
                                    file_input.name = "wysiwyg_uploadfiles[]";
                                    hidden_desc_input.setAttribute('type', 'hidden');
                                    hidden_desc_input.name = "wysiwyg_description[]";
                                    hidden_desc_input.value = old_file_name + '||' + file_description;
                                    hidden_filename_input.setAttribute('type', 'hidden');
                                    hidden_filename_input.name = "wysiwyg_filenames[]";
                                    hidden_filename_input.value = old_file_name + '||' + new_file_name;
                                    object_file.setAttribute('data', data_attribute);
                                    del_btn.textContent = 'X';
                                    del_btn.getAttribute('onclick');
                                    /*
                                    del_btn.onclick = function (e) {
                                      e.preventDefault();
                                      this.parentNode.parentNode.removeChild(this.parentNode);
                                    }

                                    div_input_object.appendChild(file_input);
                                    div_input_object.appendChild(object_file);
                                    div_input_object.appendChild(hidden_filename_input);
                                    div_input_object.appendChild(hidden_desc_input);
                                    div_input_object.appendChild(del_btn);
                                    console.log(div_input_object);
                                    */
                                    /*
                                    reader.addEventListener("load", function () {
                                      object_file.data = reader.result;
                                    }, false);

                                    if (file) {
                                      reader.readAsDataURL(file);
                                    }
                                    div_input_object.appendChild(file_input);
                                    div_input_object.appendChild(object_file);
                                    */

                                    /*
                                    console.log($modal);
                                    var data = new FormData();
                                    console.log(values);
                                    console.log(file);
                                    data.append(trumbowyg.o.plugins.pdfupload.fileFieldName, file);
                                    console.log(data);
                                    trumbowyg.o.plugins.pdfupload.data.map(function (cur) {
                                        data.append(cur.name, cur.value);
                                        console.log(cur.name);
                                        console.log(cur.value);
                                        console.log(data);
                                    });

                                    $.map(values, function(curr, key){
                                      console.log(key);
                                      console.log(curr);
                                        if(key !== 'file') {
                                            console.log(key);
                                            console.log(curr);

                                            data.append(key, curr);

                                            console.log(data);
                                        }
                                    });

                                    if ($('.' + prefix + 'progress', $modal).length === 0) {
                                        $('.' + prefix + 'modal-title', $modal)
                                            .after(
                                                $('<div/>', {
                                                    'class': prefix + 'progress'
                                                }).append(
                                                    $('<div/>', {
                                                        'class': prefix + 'progress-bar'
                                                    })
                                                )
                                            );
                                    }


                                    $.ajax({
                                        url: trumbowyg.o.plugins.pdfupload.serverPath,
                                        //headers: trumbowyg.o.plugins.pdfupload.headers,
                                        //xhrFields: trumbowyg.o.plugins.pdfupload.xhrFields,
                                        type: 'POST',
                                        data: data,
                                        cache: false,
                                        dataType: 'json',

                                        progressPdfUpload: function (e) {
                                            $('.' + prefix + 'progress-bar').stop().animate({
                                                width: Math.round(e.loaded * 100 / e.total) + '%'
                                            }, 200);
                                        },

                                        success: function (data) {
                                            if (trumbowyg.o.plugins.pdfupload.success) {
                                                trumbowyg.o.plugins.pdfupload.success(data, trumbowyg, $modal, values);

                                            } else {
                                                if (!!getDeep(data, trumbowyg.o.plugins.pdfupload.statusPropertyName.split('.'))) {
                                                    var url = getDeep(data, trumbowyg.o.plugins.pdfupload.urlPropertyName.split('.'));
                                                    trumbowyg.execCmd('insertPdf', url);
                                                    $('pdf[src="' + url + '"]:not([alt])', trumbowyg.$box).attr('alt', values.alt);
                                                    setTimeout(function () {
                                                        trumbowyg.closeModal();
                                                    }, 250);
                                                    trumbowyg.$c.trigger('tbwpdfuploadsuccess', [trumbowyg, data, url]);
                                                } else {
                                                    trumbowyg.addErrorOnModalField(
                                                        $('input[type=file]', $modal),
                                                        trumbowyg.lang[data.message]
                                                    );
                                                    trumbowyg.$c.trigger('tbwpdfuploaderror', [trumbowyg, data]);
                                                }
                                            }
                                        },

                                        error: trumbowyg.o.plugins.pdfupload.error || function () {
                                            trumbowyg.addErrorOnModalField(
                                                $('input[type=file]', $modal),
                                                trumbowyg.lang.pdfuploadError
                                            );
                                            trumbowyg.$c.trigger('tbwpdfuploaderror', [trumbowyg]);
                                        }
                                    });
                                    */
                                  }
                                }
                            );



                            $('input[type=file]').on('change', function (e) {
                                try {
                                    // If multiple files allowed, we just get the first.
                                    tempFile = $(this).clone();
                                    file = e.target.files[0];
                                    reader.addEventListener("load", function () {
                                      data_attribute = reader.result;
                                    }, false);
                                    if ( file ) {
                                      reader.readAsDataURL(file);
                                    }
                                    //var test = createPreview(file);
                                    //console.log(test);
                                } catch (err) {
                                    // In IE8, multiple files not allowed
                                    tempFile = $(this).clone();
                                    file = e.target.value;
                                    reader.addEventListener("load", function () {
                                      data_attribute = reader.result;
                                    }, false);
                                    if ( file ) {
                                      reader.readAsDataURL(file);
                                    }
                                    //var test = createPreview(file);
                                    //console.log(test);
                                }
                            });

                        }
                    };

                    trumbowyg.addBtnDef('pdfupload', btnDef);
                }
            }
        }
    });

    /*
    function addXhrProgressEvent() {
        if (!$.trumbowyg && !$.trumbowyg.addedXhrProgressEvent) {   // Avoid adding progress event multiple times
            var originalXhr = $.ajaxSettings.xhr;
            $.ajaxSetup({
                xhr: function () {
                    var req = originalXhr(),
                        that = this;
                    if (req && typeof req.pdfupload === 'object' && that.progressPdfUpload !== undefined) {
                        req.pdfupload.addEventListener('progress', function (e) {
                            that.progressPdfUpload(e);
                        }, false);
                    }

                    return req;
                }
            });
            $.trumbowyg.addedXhrProgressEvent = true;
        }
    }
*/
/*
    function createPreview(file) {
      //e.preventDefault();
      var div_input_object = document.createElement('DIV'),
          file_input = document.createElement('input'),
          object_file = document.createElement('object'),
          reader  = new FileReader();


      file_input.setAttribute('type', 'file');
      file_input.style = "display: none;";
      file_input.value = file;
      object_file.getAttribute('data');
      reader.addEventListener("load", function () {
        object_file.data = reader.result;
      }, false);

      if (file) {
        reader.readAsDataURL(file);
      }
      div_input_object.appendChild(file_input);
      div_input_object.appendChild(object_file);
      return div;
    }

*/
})(jQuery);
