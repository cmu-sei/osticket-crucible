Crucible plugin for osTicket
=========================

Crucible plugin for osTicket-1.18 and onward.

This repo is based on GPL2 licensed code from https://github.com/tuudik/osticket-rocketchat and modified to work with Crucible's Player application.

This repo is also based on MIT licensed code from `https://github.com/osTicket/osTicket-plugins`.

Preparing Project
=================

Clone this repo or download the zip file and place the contents into your `include/plugins` folder.

After cloning, `hydrate` the repo by downloading the third-party library dependencies. Note: you should be disconnected from the VPN for this command.

    php make.php hydrate

With the source code for the plugin installed into the include/plugins folder, you may develop the code live. For production environment, build and install the plugin as indicated below.

Building Plugins
================
Make any necessary additions or edits to plugins and build PHAR files with the `make.php` command

    php -d phar.readonly=0 make.php build crucible


This will compile a PHAR file for the plugin directory. The PHAR will be named `crucible.phar` and can be dropped into the osTicket `plugins/` folder directly.

    cp crucible.phar ../

Installing Plugin
=======================
Copy the sign in button image to the `assets` directory of osTicket.
```
    mkdir -p ../../../assets/oauth/images/
    cp resources/sketch.gif ../../../assets/oauth/images/
```

Copy the preferred logos to replace the defaults:
```
    cp resources/Software_Engineering_Institute_Unitmark_Red_and_Black.png ../../../scp/images/ost-logo.png
    cp resources/Software_Engineering_Institute_Unitmark_White.png ../../../assets/default/images/logo.png
``

Conguring SSL
=======================
Create a PEM certificte bundle valid for your site.
```
    cat my-ca-root.pem > bundle.pem; cat my-intermediate.pem >> certs/bundle.pem
```

Copy the CA certificates into /etc/ssl/certs:
```
    cp certs/bundle.pem /etc/ssl/certs/certs/bundle.pem
```

Update php.ini to point to the certs:
```
    echo curl.cainfo="/etc/ssl/certs/certs/bundle.pem" > /etc/php.ini
```

If you do not have the CA certificates, select the Ignore SSL Errors option in the plugin config.


Configuring the Plugin
========================
* Client ID: 
* Client Secret: 
* Identity Server URL: 
* Redirect URI:  
* View GUID: 
* Agent GUID List: 
* Admin GUID List:
* Player API URL: 
* Authentication: 
* Ignore SSL Errors: 


Notes
========================
The GUID Lists above can be a comma separated list of team GUIDs.

The identity server must have the client configured with the following options:
* Always Include User Claims in Id Token	- selected
* Authorization Code Grant Flow		- selected
* CORS Uri				- https://localhost
* Redirect Uri				- https://localhost/api/auth/ext
* Front Channel Logut Uri 		- https://localhost/logout.php
* Granted Application Access		- player

From notification to work, an additional client with `password` grant must be created and a user account with Player `ViewAdmin` permissions must be created.

## Reporting bugs and requesting features

Think you found a bug? Please report all Crucible bugs - including bugs for the individual Crucible apps - in the [cmu-sei/crucible issue tracker](https://github.com/cmu-sei/crucible/issues). 

Include as much detail as possible including steps to reproduce, specific app involved, and any error messages you may have received.

Have a good idea for a new feature? Submit all new feature requests through the [cmu-sei/crucible issue tracker](https://github.com/cmu-sei/crucible/issues). 

Include the reasons why you're requesting the new feature and how it might benefit other Crucible users.

## License

Copyright 2023 Carnegie Mellon University. See the [LICENSE.md](./LICENSE.md) files for details.
