var H5PIntegration = H5PIntegration || {};

// If run in an iframe, use parent version of globals.
if (window.parent !== window) {
  H5P = {} || H5P;
  H5P.settings = window.parent.H5P.settings;
  jQuery = window.parent.jQuery;
}

/*jQuery(document).ready(function () {
  H5P.loadedJs = window[''] Drupal.settings.h5p !== undefined && Drupal.settings.h5p.loadedJs !== undefined ? Drupal.settings.h5p.loadedJs : [];
  H5P.loadedCss = Drupal.settings.h5p !== undefined && Drupal.settings.h5p.loadedCss !== undefined ? Drupal.settings.h5p.loadedCss : [];
  H5P.postUserStatistics = Drupal.settings.h5p !== undefined && Drupal.settings.h5p.postUserStatistics !== undefined ? Drupal.settings.h5p.postUserStatistics : false;
  H5P.ajaxPath = Drupal.settings.h5p !== undefined && Drupal.settings.h5p.ajaxPath !== undefined ? Drupal.settings.h5p.ajaxPath : '';
});*/

H5PIntegration.getContentData = function (id) {
  if (H5P.settings.content !== undefined) {
    return H5P.settings.content['cid-' + id];
  }
};

H5PIntegration.getJsonContent = function (contentId) {
  var content = H5PIntegration.getContentData(contentId);
  if (content !== undefined) {
    return content.jsonContent;
  }
};

// Window parent is always available.
var locationOrigin = window.parent.location.protocol + "//" + window.parent.location.host;
H5PIntegration.getContentPath = function (contentId) {
  if (contentId !== undefined) {
    return locationOrigin + H5P.settings.contentPath + contentId + '/';
  }
  else if (H5P.settings.editor !== undefined)  {
    return H5P.settings.editor.filesPath + '/h5peditor/';
  }
};

/**
 * Get the path to the library
 *
 * @param {string} library
 *  The library identifier as string, for instance 'downloadify-1.0'
 * @returns {string} The full path to the library
 */
H5PIntegration.getLibraryPath = function (library) {
  // TODO: Does the h5peditor really need its own namespace for these things?
  var libraryPath = H5P.settings.libraryPath !== undefined ? H5P.settings.libraryPath : H5P.settings.editor.libraryPath;

  return '/' + libraryPath + library; // TODO: Get proper basepath?
};

/**
 * Get Fullscreenability setting.
 */
H5PIntegration.getFullscreen = function (contentId) {
  return H5P.settings.content['cid-' + contentId].fullScreen === '1';
};

/**
 * Should H5P Icon be displayed in action bar?
 */
H5PIntegration.showH5PIconInActionBar = function () {
  return H5P.settings.h5pIconInActionBar;
};

/**
 * Loop trough styles and create a set of tags for head.
 *
 * @param {Array} styles List of stylesheets
 * @returns {String} HTML
 */
H5PIntegration.getHeadTags = function (contentId) {
  var basePath = locationOrigin + '/'; // TODO: Get proper basepath?

  var createUrl = function (path) {
    if (path.substring(0,7) !== 'http://') {
      // Not external, add base path and cache buster.
      path = basePath + path + '?' + H5P.settings.cacheBuster;
    }
    return path;
  };

  var createStyleTags = function (styles) {
    var tags = '';
    for (var i = 0; i < styles.length; i++) {
      tags += '<link rel="stylesheet" href="' + createUrl(styles[i]) + '">';
    }
    return tags;
  };

  var createScriptTags = function (scripts) {
    var tags = '';
    for (var i = 0; i < scripts.length; i++) {
      tags += '<script src="' + createUrl(scripts[i]) + '"></script>';
    }
    return tags;
  };

  return createStyleTags(H5P.settings.core.styles)
       + createStyleTags(H5P.settings['cid-' + contentId].styles)
       + createScriptTags(H5P.settings.core.scripts)
       + createScriptTags(H5P.settings['cid-' + contentId].scripts);
};

/**
 * Define core translations.
 */
H5PIntegration.i18n = {H5P: H5P.settings.i18n};

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
  return H5P.jQuery('#h5p-admin-container'); 
};
