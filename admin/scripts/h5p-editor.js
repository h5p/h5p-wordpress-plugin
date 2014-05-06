var H5PEditor = H5PEditor || {};

(function ($) {
  H5PEditor.init = function () {
    H5PEditor.$ = H5P.jQuery;
    H5PEditor.basePath = H5P.settings.editor.libraryUrl;
    H5PEditor.fileIcon = H5P.settings.editor.fileIcon;
    H5PEditor.ajaxPath = H5P.settings.editor.ajaxPath;
    
    // Semantics describing what copyright information can be stored for media.
    H5PEditor.copyrightSemantics = H5P.settings.editor.copyrightSemantics;
    
    if (H5P.settings.editor.nodeVersionId !== undefined) {
      H5PEditor.contentId = H5P.settings.editor.nodeVersionId;
    }
    
    var h5peditor;
    var $type = $('input[name="action"]');
    var $upload = $('.h5p-upload');
    var $create = $('.h5p-create').hide();
    var $editor = $('.h5p-editor');
    var $library = $('input[name="library"]');
    var $params = $('input[name="parameters"]');
    var library = $library.val();
    
    $type.change(function () {
      if ($type.filter(':checked').val() === 'upload') {
        $create.hide();
        $upload.show();
      }
      else {
        $upload.hide();
        if (h5peditor === undefined) {
          h5peditor = new ns.Editor(library, JSON.parse($params.val()));
          h5peditor.replace($editor);
        }
        $create.show();
      }
    });

    if (library) {
      $type.filter('input[value="create"]').attr('checked', true).change();
    }

    $('#h5p-content-form').submit(function () {
      if (h5peditor !== undefined) {
        var params = h5peditor.getParams();
        if (params !== undefined) {
          $library.val(h5peditor.getLibrary());
          $params.val(JSON.stringify(params));
        }
      }
    });
    
    var $title = $('#h5p-content-form #title');
    var $label = $title.prev();
    $title.focus(function () {
      $label.addClass('screen-reader-text');
    }).blur(function () {
      if ($title.val() === '') {
        $label.removeClass('screen-reader-text');
      }
    }).focus();
  };
  
  H5PEditor.getAjaxUrl = function (action, parameters) {
    var url = H5P.settings.editor.ajaxPath + action;
    
    if (parameters !== undefined && parameters instanceof Object) {
      for (var property in parameters) {
        if (parameters.hasOwnProperty(property)) {
          url += '&' + property + '=' + parameters[property];
        }
      }
    }
    
    return url;
  };

  $(document).ready(H5PEditor.init);
})(H5P.jQuery);
