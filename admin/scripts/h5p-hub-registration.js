(function () {
  var wasInitialized = false;

  function initialize() {
    if (wasInitialized) {
      return;
    }

    wasInitialized = true;

    var data = H5POERHubRegistration.H5PContentHubRegistration;
    data.container = document.getElementById('h5p-hub-registration');
    H5PHub.createRegistrationUI(data);
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
