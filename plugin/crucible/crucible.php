<?php
// Copyright 2021 Carnegie Mellon University. All Rights Reserved.
// Released under a GNU GPL 2.0-style license. See LICENSE.md in the project root for license information.

require_once(INCLUDE_DIR.'class.signal.php');
require_once(INCLUDE_DIR.'class.plugin.php');
require_once('config.php');

class CruciblePlugin extends Plugin {
	var $config_class = "CruciblePluginConfig";

	function bootstrap() {
		$config = $this->getConfig();

		# ----- Identity ---------------------
		$identity = $config->get('enabled');
		require_once('auth.php');
		if (in_array($identity, array('all', 'staff'))) {
			require_once('authStaff.php');
			StaffAuthenticationBackend::register(
				new StaffAuthBackend($this->getConfig()));
		}
		if (in_array($identity, array('all', 'client'))) {
			require_once('authClient.php');
			UserAuthenticationBackend::register(
				new ClientAuthBackend($this->getConfig()));
		}

		# ----- Notifications -----------------
		$notify = $config->get('notify');
		if ($notify) {
			require_once('notifications.php');
			$notifications = new CrucibleNotificationPlugin($config);
		}

	}
}

require_once(INCLUDE_DIR.'UniversalClassLoader.php');
use Symfony\Component\ClassLoader\UniversalClassLoader_osTicket;
$loader = new UniversalClassLoader_osTicket();
$loader->registerNamespaceFallbacks(array(
	dirname(__file__).'/lib'));
$loader->register();

