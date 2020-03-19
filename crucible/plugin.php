/*
Crucible Plugin for osTicket
Copyright 2020 Carnegie Mellon University.
NO WARRANTY. THIS CARNEGIE MELLON UNIVERSITY AND SOFTWARE ENGINEERING INSTITUTE MATERIAL IS FURNISHED ON AN "AS-IS" BASIS. CARNEGIE MELLON UNIVERSITY MAKES NO WARRANTIES OF ANY KIND, EITHER EXPRESSED OR IMPLIED, AS TO ANY MATTER INCLUDING, BUT NOT LIMITED TO, WARRANTY OF FITNESS FOR PURPOSE OR MERCHANTABILITY, EXCLUSIVITY, OR RESULTS OBTAINED FROM USE OF THE MATERIAL. CARNEGIE MELLON UNIVERSITY DOES NOT MAKE ANY WARRANTY OF ANY KIND WITH RESPECT TO FREEDOM FROM PATENT, TRADEMARK, OR COPYRIGHT INFRINGEMENT.
Released under a GNU GPL 2.0-style license, please see license.txt or contact permission@sei.cmu.edu for full terms.
[DISTRIBUTION STATEMENT A] This material has been approved for public release and unlimited distribution.  Please see Copyright notice for non-US Government use and distribution.
Carnegie Mellon® and CERT® are registered in the U.S. Patent and Trademark Office by Carnegie Mellon University.
This Software includes and/or makes use of the following Third-Party Software subject to its own license:
1. osTicket Plugins (https://github.com/osTicket/osTicket-plugins/blob/develop/LICENSE) Copyright 2013 Free Software Foundation, Inc..
2. osticket-rocketchat (https://github.com/tuudik/osticket-rocketchat/blob/master/LICENSE) Copyright 2016 Tuudik, laufhannes, thammanna.
DM20-0195
*/

<?php

return array(
    'id' =>             'osticket:crucible',
    'version' =>        '0.1',
    'name' =>           'Oauth2 Authentication and Notifications',
    'author' =>         'Carnegie Mellon University',
    'description' =>    'Provides a configurable authentication backend
        for authenticating staff and clients using an OAUTH2 server
	interface and provides ticket thread notifications to the
	new Crucible Player API.',
    'url' =>            'http://www.cert.org',
    'plugin' =>         'crucible.php:CruciblePlugin',
    'requires' => array(
        "jumbojett/openid-connect-php" => array(
            "version" => "*",
            "map" => array(
                "jumbojett/openid-connect-php/src" => 'lib/jumbojett/openid-connect-php',
            )
        ),
        "phpseclib/phpseclib" => array(
            "version" => "*",
            "map" => array(
                "phpseclib/phpseclib/phpseclib" => 'lib/phpseclib',
            )
        ),
    )
);

?>

