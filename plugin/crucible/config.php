<?php
// Copyright 2021 Carnegie Mellon University. All Rights Reserved.
// Released under a GNU GPL 2.0-style license. See LICENSE.md in the project root for license information.

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
			'default' => 'osticket',
			'configuration' => array('size' => 60, 'length' => 100),
		)),
		'client-secret' => new PasswordField(array(
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
		'scopes' => new TextboxField(array(
			'label' => 'Identity Scopes',
			'default' => 'email openid profile player',
			'configuration' => array('size' => 60, 'length' => 100),
		)),
		'enabled' => clone $modes,
		'identity-email' => new BooleanField(array(
			'label' => 'Use Identity Email',
			'configuration' => array('desc' => 'Check to overwrite users osTicket email with Identity email on login'),
			'default' => true
		)),
		'player_noun' => new ChoiceField(array(
			'label' => 'Player noun to use',
			'default' => 'views',
			'choices' =>
				array(
					'views' => 'Views',
					'exercises' => 'Exercises'
				),
			'configuration'=>array(
				'multiselect' => false,
			),
		)),
		// player settings
		'player' => new SectionBreakField(array(
			'label' => 'Player and Team Settings',
		)),
		'view-guid' => new TextboxField(array(
			'label' => 'View GUID',
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
			'default' => 'osticket.notify',
			'configuration' => array('size' => 60, 'length' => 100),
		)),
		'notify-client-secret' => new PasswordField(array(
			'label' => 'Client Secret',
			'configuration' => array('size' => 60, 'length' => 100),
		)),
		'notify-user' => new TextboxField(array(
			'label' => 'Username',
			'configuration' => array('size' => 60, 'length' => 100),
		)),
		'notify-pass' => new PasswordField(array(
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
			'default' => '180',
			'configuration' => array('size' => 20, 'length' => 20),
		)),
		'notify' => new BooleanField(array(
			'label' => 'Notify',
			'configuration' => array('desc' => 'Check to enable notifications'),
		)),
	);
	}
}

