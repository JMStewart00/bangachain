REQUIREMENTS
------------

##### Browser #####

Not all browsers support the webcam spec, IE11 and older will not support it,
but all modern versions of Chrome, Firefox, Edge, Opera and Safari will.

##### Security #####

Most browsers will only allow you to access the camera over a secure connection,
so you will need to run your site via https, even for development environments
unless they are hosted on localhost. Some browsers, like Firefox, will allow you 
to override this, but you have to do so on every page load.

INSTALLATION
------------

Additional javascript libraries are required.

##### quaggaJS #####

This is the main library that does the image processing of barcodes.

1. Download the most recent webpacked file from github:
https://raw.githubusercontent.com/serratus/quaggaJS/master/dist/quagga.min.js
2. Copy it to: `webroot/libraries/quagga/`

You may have to make the libraries folder manually.

##### adapter #####

This library assists in compatability with the webRTC spec and is required
 by quaggaJS

1. Download the most recent file from github: 
https://webrtc.github.io/adapter/adapter-latest.js
2. Copy it to: `webroot/libraries/adapter`
