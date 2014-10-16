// Create content results data view
(function ($) {
  $(document).ready(function () {
    for (var id in H5P.settings.dataViews) {
      if (H5P.settings.dataViews.hasOwnProperty(id)) {
        var dataView = H5P.settings.dataViews[id];

        var wrapper = $('#' + id).get(0);
        if (wrapper !== undefined) {
          new H5PDataView(
            wrapper,
            dataView.source,
            dataView.headers,
            dataView.l10n,
            {
              table: 'wp-list-table widefat fixed'
            },
            dataView.filters
          );
        }
      }
    }
  });
})(H5P.jQuery);
