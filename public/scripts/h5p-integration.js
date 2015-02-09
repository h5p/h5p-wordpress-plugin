// If run in an iframe, use parent version of globals.
if (window.self !== window.top) {
  H5P.settings = window.parent.H5P.settings;
}

H5P.jQuery(document).ready(function () {
  /**
   * Define core translations.
   */
  H5PIntegration.i18n = {H5P: H5P.settings.i18n};

  H5P.loadedJs = H5P.settings.loadedJs;
  H5P.loadedCss = H5P.settings.loadedCss;
  H5P.postUserStatistics = H5P.settings.postUserStatistics;
  H5P.ajaxPath = H5P.settings.ajaxPath;
  H5P.url = H5P.settings.url;
  H5P.l10n = {H5P: H5P.settings.i18n};
  H5P.contentDatas = H5P.settings.content;

  H5P.init();
});

/**
 * Loop trough styles and create a set of tags for head.
 *
 * @param {Array} styles List of stylesheets
 * @returns {String} HTML
 */
H5P.getHeadTags = function (contentId) {
  var basePath = window.parent.location.protocol + "//" + window.parent.location.host + '/'; // TODO: Get proper basepath?

  var createStyleTags = function (styles) {
    var tags = '';
    for (var i = 0; i < styles.length; i++) {
      tags += '<link rel="stylesheet" href="' + styles[i] + '">';
    }
    return tags;
  };

  var createScriptTags = function (scripts) {
    var tags = '';
    for (var i = 0; i < scripts.length; i++) {
      tags += '<script src="' + scripts[i] + '"></script>';
    }
    return tags;
  };

  return createStyleTags(H5P.settings.core.styles) +
    createStyleTags(H5P.settings['cid-' + contentId].styles) +
    createScriptTags(H5P.settings.core.scripts) +
    createScriptTags(H5P.settings['cid-' + contentId].scripts);
};


/**
 * @namespace H5PIntegration
 * Only used by libraries admin
 */
var H5PIntegration = H5PIntegration || {};

/**
 *  Returns an object containing a library metadata
 *
 *  @returns {object} { listData: object containing libraries, listHeaders: array containing table headers (translation done server-side) }
 */
H5PIntegration.getLibraryList = function () {
  return H5P.settings.libraries;
};

/**
 *  Returns an object containing detailed info for a library
 *
 *  @returns {object} { info: object containing libraryinfo, content: array containing content info, translations: an object containing key/value }
 */
H5PIntegration.getLibraryInfo = function () {
  return H5P.settings.library;
};

/**
 * Get the DOM element where the admin UI should be rendered
 *
 * @returns {jQuery object} The jquery object where the admin UI should be rendered
 */
H5PIntegration.getAdminContainer = function () {
  return H5P.jQuery('#h5p-admin-container').html('');
};

H5PIntegration.extraTableClasses = 'wp-list-table widefat fixed';
