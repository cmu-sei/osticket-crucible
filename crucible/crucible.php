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

