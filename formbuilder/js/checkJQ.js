function includeJsFiles() {
    $.getScript("https://cdnjs.cloudflare.com/ajax/libs/izimodal/1.5.1/js/iziModal.min.js", function () {
        $('.custom-modal.ws-formbuilder').each(function (index, modal) {
            $(modal).removeClass('hidden');
        });

        $('.custom-modal.ws-formbuilder').iziModal({
            overlayClose: true,
            overlayColor: 'rgba(0, 0, 0, 0.7)',
            width: '50%'
        });

        $.getScript('https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js');
        //showAllFiles();
        //let path = '/extensions/WSForm/formbuilder/js/';
        var path = mw.config.get('wgScriptPath');
        if( path === null || !path ) {
            path = '/extensions/WSForm/formbuilder/js/';
        } else {
            path = path + '/extensions/WSForm/formbuilder/js/';
        }

        $.getScript(path + 'Element.js');
        $.getScript(path + 'addSortElements.js');
        $.getScript(path + 'editElements.js');
        $.getScript(path + 'cloneLoadElements.js');
        $('#custom-start-modal').iziModal('open');

        $('#btn-toggle-grid').on('click', function(e) {
            $('.form-container').toggleClass('grid-visible');
            ($(this).text() === "Show grid") ? $(this).text("Hide grid") : $(this).text("Show grid");
        });

        $('#btn-toggle-hidden-fields').on('click', function (e) {
            // menubar elements
            $('.menu-not-hidden').toggleClass('hidden');
            $('.menu-hidden').toggleClass('hidden');

            // editor elements
            $('.editor-not-hidden').toggleClass('hidden');
            $('.editor-hidden').toggleClass('hidden');

            ($(this).text() === "Show hidden fields") ? $(this).text("Hide hidden fields") : $(this).text("Show hidden fields");
        });
    });
}

wachtff(includeJsFiles);