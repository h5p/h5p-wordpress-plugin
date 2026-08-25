(function () {
  var wasInitialized = false;

  function initialize() {
    if (wasInitialized) {
      return;
    }

    wasInitialized = true;

    var publish = document.getElementById('h5p-hub-sharing');
    if (!publish.classList.contains('processed')) {
      publish.classList.add('processed');
      H5PHub.createSharingUI(publish, H5POERHubSharing.h5pContentHubPublish);
    }
  }

  if (document.readyState !== 'loading') {
    initialize();
  }
  else {
    document.addEventListener('readystatechange', function () {
      initialize();
    });
  }
})()
