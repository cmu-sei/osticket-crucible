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

require_once INCLUDE_DIR . 'class.plugin.php';

class CruciblePluginConfig extends PluginConfig {

	function getOptions() {
		$modes = new ChoiceField(array(
			'label' => 'Authentication',
			'choices' => array(
				'disabled' => 'Disabled',
				'staff' => 'Agents Only',
				'client' => 'Clients Only',
				'all' => 'Agents and Clients',
		)));

		return array(
		// oauth2 settings
		'identity' => new SectionBreakField(array(
			'label' => 'Authentication Settings',
		)),
		'client-id' => new TextboxField(array(
			'label' => 'Client ID',
			'configuration' => array('size' => 60, 'length' => 100),
		)),
		'client-secret' => new TextboxField(array(
			'label' => 'Client Secret',
			'configuration' => array('size' => 60, 'length' => 100),
		)),
		'identity-url' => new TextboxField(array(
			'label' => 'Identity Server URL',
			'configuration' => array('size' => 60, 'length' => 100),
		)),
		'redirect-uri' => new TextboxField(array(
			'label' => 'Redirect URI',
			'configuration' => array('size' => 60, 'length' => 100),
		)),
		'enabled' => clone $modes,
		// player settings
		'player' => new SectionBreakField(array(
			'label' => 'Player and Team Settings',
		)),
		'exercise-guid' => new TextboxField(array(
			'label' => 'Exercise GUID',
			'configuration' => array('size' => 60, 'length' => 100),
		)),
		'agent-guid-list' => new TextboxField(array(
			'label' => 'Agent GUID List',
			'configuration' => array('size' => 60, 'length' => 500),
		)),
		'admin-guid-list' => new TextboxField(array(
			'label' => 'Admin GUID List',
			'configuration' => array('size' => 60, 'length' => 500),
		)),
		'player-api-url' => new TextboxField(array(
			'label' => 'Player API URL',
			'configuration' => array('size' => 60, 'length' => 100),
		)),
		'ignore-ssl' => new BooleanField(array(
			'label' => 'Ignore SSL Errors',
			'configuration' => array('desc' => 'Check to ignore SSL errors for player API certificate'),
		)),
		'update-org' => new BooleanField(array(
			'label' => 'Update Organization',
			'configuration' => array('desc' => 'Check to change user org when user team has changed'),
		)),
		'close-tickets' => new BooleanField(array(
			'label' => 'Close Tickets',
			'configuration' => array('desc' => 'Check to close open tickets when changing user team'),
		)),
		'remove-collabs' => new BooleanField(array(
			'label' => 'Remove Collaboration',
			'configuration' => array('desc' => 'Check to remove user from tickets when changing user team'),
		)),
		'add-collabs' => new BooleanField(array(
			'label' => 'Add Collaboration',
			'configuration' => array('desc' => 'Check to add user to open tickets when changing user team'),
		)),
		'add-extended-access' => new BooleanField(array(
			'label' => 'Add Extended Access',
			'configuration' => array('desc' => 'Check to grant view only access across departments for agents '),
		)),
		// notification settings
		'notifications' => new SectionBreakField(array(
			'label' => 'Notification Settings',
		)),
		'notify-client-id' => new TextboxField(array(
			'label' => 'Client ID',
			'configuration' => array('size' => 60, 'length' => 100),
		)),
		'notify-client-secret' => new TextboxField(array(
			'label' => 'Client Secret',
			'configuration' => array('size' => 60, 'length' => 100),
		)),
		'notify-user' => new TextboxField(array(
			'label' => 'Username',
			'configuration' => array('size' => 60, 'length' => 100),
		)),
		'notify-pass' => new TextboxField(array(
			'label' => 'Password',
			'configuration' => array('size' => 60, 'length' => 100),
		)),
		'text-escape' => new BooleanField(array(
			'id' => 'text-escape',
			'label' => 'Escape text',
			'configuration' => array(
				'desc' => 'Check to escape text (You must have <a style="display: inline;" href="https://github.com/soundasleep/html2text/blob/master/src/Html2Text.php">Html2Text</a> in plugin root directory for full functionality)'
			)
		)),
		'text-doublenl' => new BooleanField(array(
			'id' => 'text-doublenl',
			'label' => 'Remove double newlines',
			'configuration' => array('desc' => 'Check to remove double newlines'),
		)),
		'text-length' => new TextboxField(array(
			'label' => 'Text length to show',
			'configuration' => array('size' => 20, 'length' => 20),
		)),
		'notify' => new BooleanField(array(
			'label' => 'Notify',
			'configuration' => array('desc' => 'Check to enable notifications'),
		)),
	);
	}
}

