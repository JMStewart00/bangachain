REQUIREMENTS
------------

##### Browser #####

You may have issues with older browsers, but most modern browsers have good
websocket support.

##### Security #####

It is recommended to do this via WSS and is what this module does by default. We
m ay add an option for insecure later, but I am not sure it is recommended.

INSTALLATION
------------

Recommended to install via composer, as the rachet websocket library is
required.

USAGE
------------

You will need to specify the websocket info on the Customer Display settings
page. There are 2 options for internal and external, internal is what the
server will run as and external is what the clients will look for, if you
have a proxy or port forwarding these may not be the same.

Now run the WebSocket server to handle passing the transactions between the
cashier and customer displays. It will automatically pickup the config that you
set earlier and attempt to start the server on the correct port. A supervisor
config is included to run the server like a service if you wish. For more
details see the Ratchet documentation. http://socketo.me/docs/deploy

Basic
```
cd webroot
php modules/contrib/commerce_pos/modules/customer_display/server.php
```

Supervisor
```
cd webroot/modules/contrib/commerce_pos/modules/customer_display
sudo supervisord -c supervisor.conf
```

To make the display look nicest, it is recommended to run your browser in
full screen mode(F11). The display does not have to be running in the same
browser or even the same computer, but it does require you to login and
select the register you wish to pair with first.
