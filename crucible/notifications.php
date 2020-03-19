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

require_once('lib/jumbojett/openid-connect-php/OpenIDConnectClient.php');

use Jumbojett\OpenIDConnectClient;

class CrucibleNotificationPlugin
{
	function __construct($config) {
		$this->config = $config;

		Signal::connect('model.created', array($this, 'onThreadEntryCreated'), 'ThreadEntry');
	}

	function onThreadEntryCreated($entry) {
		global $ost;

		$this->site = $ost->getConfig()->getUrl();

		// the title is always null, so set it to the label
		$this->body = $entry->getBody() ?: $entry->ht['body'] ?: 'No content';
		$this->ticket = $entry->getThread()->getObject();
		$this->entry = $entry;

		if ($this->entry->getType() == 'M') {
			//from user to agents
			$this->processMessage();
		} else if ($this->entry->getType() == 'R') {
			//from agent to user
			$this->processResponse();
		} else if ($this->entry->getType() == 'N') {
			// from agent to agents
			$this->processNote();
		}
	}

	function processMessage() {

		$this->fromGuid = $this->entry->getUser()->getAccount()->username;

		$messages = $this->ticket->getNumMessages();
		if ($messages == 1) {
			// this is the first message in the thread - new ticket
			$label = 'Ticket Created';
		} else if ($this->ticket->isClosed()) {
			// ticket is being reopened
			$label = 'Ticket Reopened';
		} else {
			$label = 'Ticket Message';
		}
		$subject =  $label . ' by ' . $this->entry->getPoster();

		$notification = array(
			'subject' => $subject,
			//'from' => $this->fromGuid,
			'link' => "",
			'priority' => "normal",
			'text' => $this->escapeText($this->body),
		);

		// determine assigned agent team
		foreach ($this->ticket->getAssignees() as $assignee) {
			// we have assignees so we determine who they are
			// looks like it can only be assigned to one team and one agent at time
			if (!$assignee instanceof AgentsName) {
				//team name
				$teamid = Team::getIdByName($assignee);
				// if desired, we could lookup and send to all members of the team
				//echo "team $teamid <br>";
			} else if ($this->ticket->isClosed()) {
				// if closed, assignee is null but assignees has the closer agentsname
				//lookup the guid of the agent by name
				$assigneeGuid = $this->getStaffUserNameByName($assignee);
			}
		}

		// lookup the assignee, if present
		if ($this->ticket->getAssignee()) {
			$assigneeGuid = $this->ticket->getAssignee()->getUserName();
			$notification['link'] = $this->site . 'scp/tickets.php?id=' . $this->ticket->getId();
			$this->sendToUser($notification, $assigneeGuid);
			// echo "sent to assignee $assigneeGuid<br>";
		}

		if (!$assigneeGuid) {
			// there are no assignees on ticket so we send to the tickets department
			$this->sendToDepartment($notification);
		}

		// send to all respondents
		$this->sendToUserRespondents($notification);
		$this->sendToStaffRespondents($notification, $assigneeGuid);
	}

	function processResponse() {
		$subject = 'Ticket Response by ' . $this->entry->getPoster();
		$this->fromGuid = $this->entry->getRespondent()->getUserName();

		$notification = array(
			'subject' => $subject,
			//'from' => $this->fromGuid,
			'link' => "",
			'priority' => "normal",
			'text' => $this->escapeText($this->body),
		);

		// lookup the assignee, if present
		if ($this->ticket->getAssignee()) {
			$assigneeGuid = $this->ticket->getAssignee()->getUserName();
			if ($this->fromGuid != $assigneeGuid) {
				$notification['link'] = $this->site . 'scp/tickets.php?id=' . $this->ticket->getId();
				$this->sendToUser($notification, $assigneeGuid);
				// echo "sent to assignee $assigneeGuid<br>";
			}
		}

		$this->sendToUserRespondents($notification);
		$this->sendToStaffRespondents($notification, $assigneeGuid);
	}

	function processNote() {

		$subject = 'Ticket Note by ' . $this->entry->getPoster();
		$ticketLink = $this->site . 'scp/tickets.php?id=' . $this->ticket->getId();
		if (!$this->fromGuid) {
			$this->fromGuid = $this->entry->getStaff()->getUserName();
		}

		$notification = array(
			'subject' => $subject,
			//'from' => $this->fromGuid,
			'link' => $ticketLink,
			'priority' => "normal",
			'text' =>  $this->escapeText($this->body),
		);

		$this->sendToDepartment($notification);
	}

	function sendToUserRespondents($notification) {
		$notification['link'] = $this->site . 'tickets.php?id=' . $this->ticket->getId();

		// get users with entries
		$posters = ThreadEntry::objects()->filter(array(
			'thread_id' => $this->entry->getThreadId(),
		))
		->distinct('user_id');
		foreach ($posters as $poster) {
			// this includes the orignal user
			if ($poster->getUserId()) {
				$posterGuid = $poster->getUser()->getAccount()->username;
				// do not send to original user (unless we skipped it above)
				if ($posterGuid != $this->fromGuid) {
					$this->sendToUser($notification, $posterGuid);
					// echo "sent to user $posterGuid<br>";
				}
			}
		}
	}

	function sendToStaffRespondents($notification, $assigneeGuid) {
		$notification['link'] = $this->site . 'scp/tickets.php?id=' . $this->ticket->getId();

		// get staff with entries;
		$posters = ThreadEntry::objects()->filter(array(
			'thread_id' => $this->entry->getThreadId(),
		))
		->distinct('staff_id');
		foreach ($posters as $poster) {
			if ($poster->getStaffId()) {
				$posterGuid = $poster->getStaff()->username;
				// do not send to the current respondant or the assignee (sent already)
				if (($posterGuid != $this->fromGuid)  && ($posterGuid != $assigneeGuid)) {
					$this->sendToUser($notification, $posterGuid);
					// echo "sent to staff $posterGuid<br>";
				}
			}
		}
	}

	function getStaffUserNameByName($name) {
		$first = $name->getFirst();
		$last = $name->getLast();

		$users = Staff::objects()->filter(array(
			'firstname' => $first,
			'lastname' => $last,
		));

		if (count($users) == 1) {
			return $users[0]->username;
		} else {
			// not an exact match
			return null;
		}
	}

	function sendToDepartment($payload) {
		$payload['link'] = $this->site . 'scp/tickets.php?id=' . $this->ticket->getId();

		$members = $this->ticket->getDept()->getPrimaryMembers();
		foreach ($members as $member) {
			if ($member->username != $this->fromGuid) {
				$this->sendToUser($payload, $member->username);
			}
		}
	}

	function getAuthorization() {
		global $ost;

		$expiration = $this->config->get('expiration');

		// check that user hasnt updated
		$sql = 'SELECT updated FROM ' . TABLE_PREFIX . 'config WHERE `key`="token"';
        $results = db_query($sql);
        if ($results) {
			if ($results->num_rows == 1) {
					$result = $results->fetch_row();
			}

			$token_time = strtotime($result[0]);
		}

		$sql = 'SELECT updated FROM ' . TABLE_PREFIX . 'config WHERE `key`="notify-user"';
        $results = db_query($sql);
        if ($results) {
			if ($results->num_rows == 1) {
					$result = $results->fetch_row();
			}
			$user_time = strtotime($result[0]);
		}

		if ((time() >= $expiration) || ($user_time > $token_time)) {
			// get a new token
			$oidc = new OpenIDConnectClient($this->config->get('identity-url'),
			$this->config->get('notify-client-id'),
			$this->config->get('notify-client-secret'));

			if ($this->config->get('ignore-ssl')) {
				$oidc->setVerifyHost(false);
				$oidc->setVerifyPeer(false);
			}

			$oidc->addScope('email openid profile player');
			$oidc->addAuthParam(array('username'=>$this->config->get('notify-user')));
			$oidc->addAuthParam(array('password'=>$this->config->get('notify-pass')));
			$response = $oidc->requestResourceOwnerToken(TRUE);

			$token = $response->access_token;
			$expiration = $response->expires_in + time();
			$this->config->set('token', $token);
			$this->config->set('expiration', $expiration);
			$ost->logDebug("token renewal", "renewed token for " . $this->config->get('notify-client-id') . "expires " . $expiration, false);

		} else {
			// use token
			$token = $this->config->get('token');
		}
		$authorization = "Authorization: Bearer " . $token;

		return $authorization;
	}

	function sendToUser($payload, $guid) {
		global $ost;

		$authorization = $this->getAuthorization();

		if (!$guid || !$authorization) {
			return;
		}

		try {
			$data_string = utf8_encode(json_encode($payload));
			$url = $this->config->get('player-api-url') . '/exercises/' . $this->config->get('exercise-guid') . '/users/' . $guid . '/notifications';
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen($data_string),
                $authorization
			));
			if (curl_exec($ch) === false) {
				throw new Exception($url . ' - ' . curl_error($ch));
			} else {
				$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				if ($statusCode != '200') {
					throw new Exception($url . ' Http code: ' . $statusCode);
				}
			}
			curl_close($ch);
		}
		catch (Exception $e) {
			$ost->logError("notification error", $e->getMessage(), false);
		}
	}

	function escapeText($text) {
		$text = convert_html_to_text($text);
		if ($this->config->get('text-escape') == true) {
			$text = str_replace('<br />', '\n', $text);
			$text = str_replace('<br/>', '\n', $text);
			$text = str_replace('&', '&amp;', $text);
			$text = str_replace('<', '&lt;', $text);
			$text = str_replace('>', '&gt;', $text);
		}
		if ($this->config->get('text-doublenl') == true) {
			$text = preg_replace("/[\r\n]+/", "\n", $text);
			$text = preg_replace("/[\n\n]+/", "\n", $text);
		}
		$text = preg_replace('/[\n]+/', '', $text);

		if (($this->config->get('text-length')) && (strlen($text) >= $this->config->get('text-length'))) {
			if ($this->config->get('text-length'))
				$text = substr($text, 0, (int)$this->config->get('text-length')) . '...';
			else
				$text = substr($text, 0, 256) . '...';
		}
		return $text;
	}

}

