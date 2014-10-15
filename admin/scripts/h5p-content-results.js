// Create content results data view
(function ($) {
  $(document).ready(function () {
    var settings = H5P.settings.contentResults;
    new H5PDataView(
      $('#h5p-content-results').get(0),
      settings.source,
      settings.headers,
      settings.l10n,
      {
        table: 'wp-list-table widefat fixed'
      });
  });
})(H5P.jQuery);
