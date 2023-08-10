<?php
// Copyright 2021 Carnegie Mellon University. All Rights Reserved.
// Released under a GNU GPL 2.0-style license. See LICENSE.md in the project root for license information.

return array(
    'id' =>             'osticket:crucible',
    'version' =>        '1.1.0',
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
            "version" => "0.6.0",
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

