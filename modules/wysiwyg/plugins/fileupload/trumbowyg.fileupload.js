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

    function returnTypeOfFile(val) {
        console.log("VALUE::::"+val);
        console.log("VALUE2::::"+val.split('.').slice(-1)[0]);
      return val.split('.').slice(-1)[0];
    }

    function createDivObjectElement(file, file_description, old_file_name, new_file_name, data_attribute, data_name, files, filenames_input, formID, type) {
      var div_input_object = document.createElement('DIV'),
          hidden_desc_input = document.createElement('input'),
          hidden_filename_input = document.createElement('input'),
          object_file = document.createElement('object'),
          file_input = files,
          div_object = document.createElement('DIV'),
          div_desc = document.createElement('DIV'),
          div_type = document.createElement('DIV'),
          html_bold_tag = document.createElement('B'),
          time = new Date(),
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
      div_type.classList.add('wsattaching__type');
      div_type.textContent = type;
      html_bold_tag.textContent = file_description;
      div_desc.classList.add('wsattaching__description');
      div_object.classList.add('wsattaching__preview');
      del_btn.classList.add('wsattaching__dismiss')
      del_btn.textContent = '×';
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

      div_input_object.classList.add('wsattaching__item', 'ui-sortable-handle');
      div_input_object.appendChild(file_input);
      div_object.appendChild(object_file);
      div_input_object.appendChild(hidden_filename_input);
      div_input_object.appendChild(hidden_desc_input);
      div_input_object.appendChild(del_btn);
      div_desc.appendChild(html_bold_tag);
      div_input_object.appendChild(div_object);
      div_input_object.appendChild(div_desc);
      div_input_object.appendChild(div_type);
      div_input_object.id = new_file_name;
      return div_input_object;
    }


    $.extend(true, $.trumbowyg, {
        langs: {
            // jshint camelcase:false
            en: {
                fileupload: 'fileupload',
                file: 'File',
                fileuploadError: 'Error'
            }
        },
        // jshint camelcase:true

        plugins: {
            fileupload: {
                init: function (trumbowyg) {
                    trumbowyg.o.plugins.fileupload = $.extend(true, {}, defaultOptions, trumbowyg.o.plugins.fileupload || {});
                    var btnDef = {
                        fn: function () {
                            trumbowyg.saveRange();

                            var file,
                                prefix = trumbowyg.o.prefix,
                                data_attribute,
                                tempFile,
                                //hahaha = ['png', 'gif', 'jpg', 'jpeg', 'doc', 'docx', 'xls', 'xlsx', 'pdf', 'zip', 'svg', 'mp4'],
                                reader  = new FileReader();

                                //console.log(haha);
                                reader.addEventListener("load", function () {
                                  data_attribute = reader.result;
                                }, false);

                            var $modal = trumbowyg.openModalInsert(
                                // Title
                                trumbowyg.lang.fileupload,

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
                                    var RegEx = ['[', ']', '{', '}', '|', '#', '<', '>', '%', '+', '?', '.', ','];
                                    var tempindex = file.name.lastIndexOf('.');
                                    var tfile = file.name.slice(0, tempindex);
                                    var file_type = file.name.slice(tempindex+1, file.name.length);
                                    var validFilename = true;
                                    var validFilesize = true;
                                    var errorArray = [];

                                    for ( var i = 0; i < RegEx.length; i++ ) {
                                      if ( tfile.indexOf(RegEx[i]) !== -1 ) {
                                        validFilename = false;
                                        errorArray.push(RegEx[i]);
                                      }
                                    }

                                    console.log(file.size);
                                    if ( file.size >= 50000000 ) {
                                      validFilesize = false;
                                    }
                                    if ( !validFilename ) {
                                      var errorString = errorArray.join(" ");
                                      alert("The filename isn't allowed, remove these characters please: " + errorString);
                                    }
                                    else if( validFilesize ){
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
                                          tmpFileName = returnTypeOfFile(file.name),
                                          new_file_name = pageId + '-' + d.getTime() + '.' + tmpFileName;
                                          console.log(new_file_name);
                                      for ( var i = 0; i < test$form.length; i++ ) {
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
                                          if ( selectedAttachments[i].match(old_file_name) !== null && selectedAttachments[i].match(old_file_name)[0] !== "" ) {
                                            if ( confirm('It seems like you already uploaded this file, do you want to upload it again?') ) {
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
                                            var element = createDivObjectElement(file, file_description, old_file_name, new_file_name, data_attribute, values.upload, tempFile, attachments_input, formid, file_type);
                                            $('#'+attachments_div.id).sortable({
                                              helper: "clone",
                                              placeholder: "ui-state-highlight",
                                              update: function (event, ui) {
                                                var children = this.children;
                                                var tempAttachmentsArray = [];
                                                for ( var i = 0; i < children.length; i++ ) {
                                                  for ( var k = 0; k < selectedAttachments.length; k++ ) {
                                                    if ( selectedAttachments[k] == children[i].id ) {
                                                      tempAttachmentsArray.push(selectedAttachments[k]);
                                                    }
                                                  }
                                                }
                                                attachments_input.value = tempAttachmentsArray.join(',');
                                              }
                                            });
                                            attachments_div.appendChild(element);
                                          }
                                        }


                                      }
                                  }
                                }
                            );



                            $('input[type=file]').on('change', function (e) {
                                try {
                                    // If multiple files allowed, we just get the first.
                                    console.log(e);
                                    file = e.target.files[0];
                                    var hello = $(this).clone();
                                    tempFile = e.target;
                                    //tempFile.files[0] = file;
                                    console.log(hello);
                                    console.log(tempFile);
                                    reader.addEventListener("load", function () {
                                      data_attribute = reader.result;
                                    }, false);
                                    if ( file ) {
                                      reader.readAsDataURL(file);
                                    }
                                } catch (err) {
                                    // In IE8, multiple files not allowed


                                    console.log(err);
                                    file = e.target.value;
                                    tempFile = $(this).clone();
                                    reader.addEventListener("load", function () {
                                      data_attribute = reader.result;
                                    }, false);
                                    if ( file ) {
                                      //reader.readAsDataURL(file);
                                    }
                                }
                            });

                        }
                    };

                    trumbowyg.addBtnDef('fileupload', btnDef);
                }
            }
        }
    });

})(jQuery);
