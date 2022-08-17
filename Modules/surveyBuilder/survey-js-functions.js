/**
 * Saves the form results into the slot
 * @param qid {String}
 */
const saveFormResults = (qid) => {
  const api = new mw.Api(),
      formName = $('input.questionnaire-form-title').val(),
      title = decrypt('questionnaire-share-salt', qid);


  /**
   * Checks form validation before save
   * @param form {HTMLFontElement}
   * @returns {boolean}
   */
  const checkFormValidation = (form) => {
    let check = true;
    $(form).find('input,select,textarea').each((i, input) => {
      if (input.required && !$(input).val() && input.name) {
        check = false;
      }
    });
    return check;
  };

  /**
   * Saves the form with correct questions
   * @param junctionObject {Object}
   */
  const saveForm = (junctionObject) => {
    if (junctionObject === {}) return;
    const formJSON = {
      userId: mw.user.getId() === 0 ? mw.user.sessionId() : mw.user.getId(),
      date: getCurrentDate()
    };

    if (!checkFormValidation($('form.questionnaire-form')[0])) {
      mw.notify('Not every required field is filled in!', {type: 'error'});
      return;
    }

    $('form.questionnaire-form').find('input,select,textarea').each((i, element) => {
      if (element.type === 'hidden') return;
      if ((element.type === 'radio' || element.type === 'checkbox') && !element.checked) return;
      if (!element.value || element.value === '') return;
      if (!element.name || element.name === '') return;

      formJSON[junctionObject[element.name]] = element.value;
    });


    // Api params to get the saved results
    const paramsGetSlot = {
      action: 'readslot',
      format: 'json',
      title: title,
      slot: 'ws-questionnaire-results'
    };

    // get the saved results
    api.get(paramsGetSlot)
    .fail(err => {
      if (err === 'slotdoesnotexist') {
        $('#questionnaire-results-textarea').val(JSON.stringify([formJSON]));
        $('#save_form').trigger('submit');
      }
    })
    .done(data => {
      let results = [];
      console.log(data);

      // check if there are saved results, if so set {results}
      if (data.result) {
        results = JSON.parse(data.result);
      }

      // get the index of a result that has been filled by this user
      const index = getIndexOfResultWithWantedSearchKey(results);

      // check if there was already a result for this user, if so overwrite it
      if (index >= 0) results[index] = formJSON;
      else results.push(formJSON);

      $('#questionnaire-results-textarea').val(JSON.stringify(results));
      $('#save_form').trigger('submit');
    });
  };

  // api params to get the questionnaire form object
  const paramsSlotCalls = {
    action: 'readslot',
    format: 'json',
    title: formName,
    slot: 'ws-questionnaire-form'
  };


  // api get call
  api.get(paramsSlotCalls).done(data => {
    if ( data.result ) {
      // get the junction table object from the json and save the form
      const { junctionTable } = JSON.parse(data.result);
      saveForm(junctionTable);
    }
  });
};


/**
 * fill in the results if possible
 * @param title {String}
 */
const fillFormWithResults = (title) => {
  const api = new mw.Api();

  // check if there is a form that needs these functionalities
  if ($('form.questionnaire-form').length === 0) return;

  /**
   * handles the filled results
   * @param results {Object[]}
   */
  const handleFilledResults = (results) => {
    const index = getIndexOfResultWithWantedSearchKey(results);

    // check if the results object has the logged in user results
    if (index >= 0) {
      const filledValues = results[index];

      // api params to get the questionnaire form object
      const paramsForm = {
        action: 'readslot',
        title: $('input.questionnaire-form-title').val(),
        format: 'json',
        slot: 'ws-questionnaire-form'
      };

      // get the questionnaire form api call
      api.get(paramsForm).done(data => {
        if ( !data.result ) return;

        // get the junctionTable from the JSON
        const { junctionTable } = JSON.parse(data.result);

        // loop through the form and set the filled value if name is linked
        $('form.questionnaire-form').find('input,select,textarea').each((i, input) => {
          if (junctionTable[input.name] && filledValues[junctionTable[input.name]]) {
            if (input.type === 'radio' || input.type === 'checked') {
              input.checked = filledValues[junctionTable[input.name]] === input.value;
              if (input.checked) $(input).trigger('change');
            } else {
              input.value = filledValues[junctionTable[input.name]];
              $(input).trigger('change');

              if (!$(input).is('select')) {
                $(input).trigger('input');
              }
            }
          }

          // check also for data-name-attribute, for show on select options
          const dataName = $(input).data('name-attribute');
          if (junctionTable[dataName] && filledValues[junctionTable[dataName]]) {
            if (input.type === 'radio' || input.type === 'checked') {
              input.checked = filledValues[junctionTable[dataName]] === input.value;
              if (input.checked) $(input).trigger('change');
            } else {
              input.value = filledValues[junctionTable[dataName]];
              $(input).trigger('change');
              if (!$(input).is('select')) {
                $(input).trigger('input');
              }
            }
          }
        });
      });
    }
  };

  const params = {
    action: 'readslot',
    format: 'json',
    title: title,
    slot: 'ws-questionnaire-results'
  };

  // get the results json
  api.get(params).done(data => {
    console.log(data);
    if (data.result) {
      handleFilledResults(JSON.parse(data.result));
    }
  });
};

/**
 * gets the index of the result with the wanted search key
 * @param results {Object[]}
 * @returns {Number}
 */
const getIndexOfResultWithWantedSearchKey = (results) => {
  const type = $('.questionnaire-type').val();

  switch (type) {
    case 'Date':
      return results.findIndex(r => r.date === getCurrentDate());
    case 'Users':
      const uid = mw.user.getId() === 0 ? mw.user.sessionId() : mw.user.getId();
      return results.findIndex(r => r.userId === uid);
    default:
      return -1;
  }
};

const getCurrentDate = () => {
  const d = new Date();
  return `${d.getDate()}-${(d.getMonth() + 1)}-${d.getFullYear()}`;
};

const checkForResultsAndHideSelect = (title) => {
  const params = {
    action: 'readslot',
    title: title,
    format: 'json',
    slot: 'ws-questionnaire-results'
  };

  new mw.Api().get(params).done(data => {
    if (data.result) {
      $('.sidebar-item:has(#form_title-tokens)').hide();
    }
  });
}

/**
 * handle the multiple sections in a form
 */
const handleMultipleSections = () => {
  /**
   * make the other sections in this form hidden
   * @param form {HTMLFontElement|jQueryObject}
   * @param sectionId {int}
   */
  const makeOtherSectionsHidden = (form, sectionId) => {
    $(form).find('div.form-section').each((i, section) => {
      if ($(section).data('section-id') === sectionId) {
        $(section).show(0);

        if($(form).find('div.form-section').length - 1 === i) {
          $(form).siblings('button.next-section-btn').hide(0);
          $(form).siblings('button.prev-section-btn').show(0);
        } else if (sectionId === 0) {
          $(form).siblings('button.next-section-btn').show(0);
          $(form).siblings('button.prev-section-btn').hide(0);
        } else {
          $(form).siblings('button.next-section-btn').show(0);
          $(form).siblings('button.prev-section-btn').show(0);
        }
      } else {
        $(section).hide(0);
      }
    });
  };


  $('form.questionnaire-form').each((i, form) => {
    if ($(form).find('div.form-section').length > 1) {

      const handleButtonClicks = (btn, idAddition) => {
        const f = $(btn).siblings('form.questionnaire-form')[0];
        const newSectionId = +$(f).attr('data-active-section') + idAddition;

        $(f).attr('data-active-section', newSectionId);
        makeOtherSectionsHidden(f, newSectionId);
      };


      // create next button for the pagination between sections
      const nextBtn = $('<button class="btn btn-outline-primary next-section-btn">Next</button>');

      $(nextBtn).on('click', (e) => {
        handleButtonClicks(e.target, 1);
      });

      $(nextBtn).insertAfter(form);


      const prevBtn = $('<button class="btn btn-outline-primary prev-section-btn">Prev</button>');

      $(prevBtn).on('click', (e) => {
        handleButtonClicks(e.target, -1);
      });

      $(prevBtn).insertAfter(form);



      // set the active section id to 0 and hide other ones
      $(form).attr('data-active-section', 0);
      makeOtherSectionsHidden(form, 0);
    }
  });
}

const handleMultipleForms = () => {

}

$(document).ready(function() {
  if ($('form input#questionnaire_identifier[type="hidden"]').length > 0 && mw.user.getName()) {
    $('form input#questionnaire_identifier[type="hidden"]').val(encrypt('questionnaire-share-salt', mw.config.values.wgPageName));
  }
  // check if there is a form that needs these functionalities
  if ($('form.questionnaire-form').length === 0) return;

  handleMultipleSections();

  if (!mw.user.getName()) return;

  fillFormWithResults(mw.config.values.wgPageName);
  checkForResultsAndHideSelect(mw.config.values.wgPageName);

  $('form.questionnaire-form.flex-form-hide').removeClass('flex-form-hide');
});



const getCSV = (qid) => {
  var qid = encrypt('questionnaire-share-salt', mw.config.values.wgPageName);
  console.log( "qid", qid );
  const api = new mw.Api(),
      formName = $( 'input.questionnaire-form-title' ).val(),
      title = decrypt( 'questionnaire-share-salt', qid );


  // Api params to get the saved results
  const getIt = {
    action: 'readslot',
    format: 'json',
    title: title,
    slot: 'ws-questionnaire-results'
  };

  // get the saved results
  api.get( getIt )
      .fail( err => {
        if ( err === 'slotdoesnotexist' ) {
          $( '#questionnaire-results-textarea' ).val( JSON.stringify( [formJSON] ) );
          console.log( "Slot does not exist" );
        }
      } )
      .done( data => {
        let results = [];
        console.log( data );

        // check if there are saved results, if so set {results}
        if ( data.result ) {
          results = JSON.parse( data.result );
        }
        console.log( results );
        downloadObjectAsJson( convertToCSV( results ), "results" );
      } );
}

function convertToCSV( json ) {
   var fields = Object.keys(json[0])
  var replacer = function(key, value) { return value === null ? '' : value }
  var csv = json.map(function(row){
    return fields.map(function(fieldName){
      return JSON.stringify(row[fieldName], replacer)
    }).join(',')
  })
  csv.unshift(fields.join(',')) // add header column
  csv = csv.join('\r\n');
  return csv;
}

function downloadObjectAsJson(exportObj, exportName){
  var dataStr = "data:text/csv;charset=utf-8," + encodeURI(exportObj);
  var downloadAnchorNode = document.createElement('a');
  downloadAnchorNode.setAttribute("href",     dataStr);
  downloadAnchorNode.setAttribute("download", exportName + ".csv");
  document.body.appendChild(downloadAnchorNode); // required for firefox
  downloadAnchorNode.click();
  downloadAnchorNode.remove();
}

const encrypt = (salt, text) => {
  const textToChars = (text) => text.split("").map((c) => c.charCodeAt(0));
  const byteHex = (n) => ("0" + Number(n).toString(16)).substr(-2);
  const applySaltToChar = (code) => textToChars(salt).reduce((a, b) => a ^ b, code);

  return text
      .split("")
      .map(textToChars)
      .map(applySaltToChar)
      .map(byteHex)
      .join("");
};

const decrypt = (salt, encoded) => {
  const textToChars = (text) => text.split("").map((c) => c.charCodeAt(0));
  const applySaltToChar = (code) => textToChars(salt).reduce((a, b) => a ^ b, code);
  return encoded
      .match(/.{1,2}/g)
      .map((hex) => parseInt(hex, 16))
      .map(applySaltToChar)
      .map((charCode) => String.fromCharCode(charCode))
      .join("");
};


const createShareLink = (title) => {
  title = title.replaceAll(' ', '_');
  const shareLink = `${window.location.origin}/PQ:Questionnaire?qid=${encrypt('questionnaire-share-salt', mw.config.values.wgPageName)}`;
  console.log(shareLink);
  navigator.clipboard.writeText(shareLink);
  mw.notify('Link copied to clipboard!', { type: 'success' });
};


