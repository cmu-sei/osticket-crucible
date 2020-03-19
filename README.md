Crucible plugin for osTicket
=========================

Crucible plugin for osTicket-1.8 and onward
This repo is based on GPL2 licensed code from https://github.com/tuudik/osticket-rocketchat and modified to work with Crucible.Player.API.
This repo is also based on MIT licensed code from `https://github.com/osTicket/osTicket-plugins`

Preparing Project
=================

Clone this repo or download the zip file and place the contents into your
`include/plugins` folder

After cloning, `hydrate` the repo by downloading the third-party library
dependencies. Note: you should be disconnected from the VPN for this command.

    php make.php hydrate


Building Plugins
================
Make any necessary additions or edits to plugins and build PHAR files with
the `make.php` command

    php -d phar.readonly=0 make.php build crucible


This will compile a PHAR file for the plugin directory. The PHAR will be
named `crucible.phar` and can be dropped into the osTicket `plugins/` folder
directly.

    cp crucible.phar ../

Installing Plugin
=======================
Copy the sign in button image to the `assets` directory of osTicket.
    mkdir -p ../../../assets/oauth/images/
    cp resources/sketch.gif ../../../assets/oauth/images/

Copy the preferred logos to replace the defaults:
    cp resources/Software_Engineering_Institute_Unitmark_Red_and_Black.png ../../../scp/images/ost-logo.png
    cp resources/Software_Engineering_Institute_Unitmark_White.png ../../../assets/default/images/logo.png

Conguring SSL
=======================
Create a PEM certificte bundle valid for your site.
    cat my-ca-root.pem > bundle.pem; cat my-intermediate.pem >> certs/bundle.pem

Copy the CA certificates into /etc/ssl/certs:
    cp certs/bundle.pem /etc/ssl/certs/certs/bundle.pem

Update php.ini to point to the certs:
    echo curl.cainfo="/etc/ssl/certs/certs/bundle.pem" > /etc/php.ini

If you do not have the CA certificates, select the Ignore SSL Errors option in the plugin config.


Configuring the Plugin
========================
Client ID: 

Client Secret: 

Identity Server URL: 

Redirect URI:  

Exercise GUID: 

Agent GUID List: 

Admin GUID List:

Player API URL: 

Authentication: 

Ignore SSL Errors: 


Notes
========================
The GUID Lists above can be a comma separated list of team GUIDs.

The identity server must have the client configured with the following options
Always Include User Claims in Id Token	- selected
Authorization Code Grant Flow		- selected
CORS Uri				- https://localhost
Redirect Uri				- https://localhost/api/auth/ext
Front Channel Logut Uri 		- https://localhost/logout.php
Granted Application Access		- player


