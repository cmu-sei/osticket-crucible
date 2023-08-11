<?php
// Copyright 2021 Carnegie Mellon University. All Rights Reserved.
// Released under a GNU GPL 2.0-style license. See LICENSE.md in the project root for license information.

use ohmy\Auth2;

class ClientAuthBackend extends ExternalUserAuthenticationBackend {
    static $id = "identity.client";
    static $name = "Identity Client";

    static $sign_in_image_url = ROOT_PATH . "assets/oauth/images/sketch.gif";
    static $service_name = "Identity";

    function getServiceName() {
        return $this->service_name;
    }

    function __construct($config) {
        $this->config = $config;
        $pluginInstance = $this->config->getInstance();
    	$this->service_name = $pluginInstance->name;
        $this->identity = new Auth($config);
    }

    function supportsInteractiveAuthentication() {
        return false;
    }

    function signOn() {
        // TODO: Check session for auth token
        if (isset($_SESSION[':oauth']['username'])) {
            if (($acct = ClientAccount::lookupByUsername($_SESSION[':oauth']['username']))
                    && $acct->getId()
                    && ($client = new ClientSession(new EndUser($acct->getUser())))) {
                return $client;
            } elseif (isset($_SESSION[':oauth']['profile'])) {
                $profile = $_SESSION[':oauth']['profile'];
                $info = array(
                    'username' => $_SESSION[':oauth']['username'],
                    'name' => $profile['displayName'],
                );
                return new ClientCreateRequest($this, $info['username'], $info);
            }
        }
    }

    static function signOut($user) {
        parent::signOut($user);
        unset($_SESSION[':oauth']);
    }

    function triggerAuth() {
        require_once INCLUDE_DIR . 'class.json.php';
        global $ost;

        parent::triggerAuth();
        $this->identity->triggerAuth();

        try {
            if (($this->identity->is_agent) || ($this->identity->is_admin)) {
                Http::redirect(ROOT_PATH . 'scp/login.php?do=ext&bk=identity.staff');
            } elseif ($this->checkUser()) {
                // lets first to check whether we need to update this user email and name
                $_SESSION[':oauth']['name'] = $this->identity->name;
                //$_SESSION[':oauth']['username'] = $this->identity->guid;
                $_SESSION[':oauth']['username'] = $this->identity->username;
		//var_dump($this->identity);
		//$_SESSION['_auth'] = array ();
                //var_dump($_SESSION['_auth']);

                //echo "redirect to " . ROOT_PATH . "tickets.php<br>";
                Http::redirect(ROOT_PATH . 'tickets.php');
            } else {
                Http::redirect(ROOT_PATH . 'login.php');
            }
        }
        catch (Exception $e) {
            $ost->logError("triggerAuth - Client", $e->getMessage(), false);
            throw $e;
        }
    }

    function checkUser() {

        $vars = array(
            'name' => $this->identity->name,
            'email' => $this->identity->email,
            //'username' => $this->identity->guid,
            'username' => $this->identity->username,
            'org_id' => $this->identity->org_id,
            'backend' => "identity.client",
            'sendemail' => "false"
        );

        // // lookup staff user account
        // $staff = Staff::getIdByUsername($this->identity->guid);
        // if ($staff) {
        //     // we should not allow login, redirect to admin page
        //     $_SESSION['_staff']['auth']['msg'] = 'staff must use admin portal';
        //     //Http::redirect(ROOT_PATH . 'scp/login.php');
        //     // or, we can just log them in automatically
        //     Http::redirect(ROOT_PATH . 'scp/login.php?do=ext&bk=identity.staff');
        //     //return false;
        // }
	//var_dump($this->identity->guid);

        // lookup user account
        $account = UserAccount::lookupByUsername($this->identity->username);
        if (!$account) {
//echo "creating accoutn for this user";
            // we need to create the user
            return $this->createUser($vars);
        } else {
//echo "found user<br>";
            $user = $account->getUser();
//var_dump($account);
//echo "<br><br>";
//var_dump($user);
//echo "<br><br>";
            // update user name if necessary
            if ($this->identity->name != $user->name) {
echo "update name<br>";
                $user->name = $this->identity->name;
                $user->save();
            }
            if ($this->config->get('identity-email') && (!isset($user->default_email) || $this->identity->email != $user->default_email)) {
echo "update email<br>";
                $user->default_email = $this->identity->email;
                $user->save();
            }
            // update org if necessary
            if ($this->config->get('update-org') && $user->getOrgId() != $this->identity->org_id) {
echo "update org<br>";
                    if ($this->config->get('remove-collabs')) {
                    // remove user from old ticket thread collaborators
                    $this->removeUserAsCollaborator($user->getId(), $user->getOrgId());
                }
                // close open tickets for this user
                if ($this->config->get('close-tickets')) {
                    $this->closeUserTickets($user->getId());
                }

                $org = Organization::objects()->filter(array(
                    'id'=>$this->identity->org_id,
                ))[0];

                // change the users organization
                $user->setOrganization($org);

                if ($this->config->get('add-collabs')) {
                    // add user to all existing tickets in this org
                    $this->addUserAsCollaborator($user, $org);
                }
            }
        }

        return true;
    }

    function removeUserAsCollaborator($user_id, $org_id) {

        return Collaborator::objects()->filter(array(
                'user_id'=>$user_id,
                'user__org_id'=>$org_id,
            ))
            ->delete();
    }

    function addUserAsCollaborator($user, $org) {

        // determine whether org wants to add collab to all members
        if ($org && $org->autoAddMembersAsCollabs()) {

            // find all tickets for this org
            $tickets = Ticket::objects()->filter(Q::any(array(
                'user__org_id' => $org->getId(),
            )));
            // filter for open tickets only
            $tickets->filter(Q::any(array(
                'status_id' => "1",
            )));

            foreach ($tickets as $ticket) {

                // check for this user as a collaborator on this ticket
                $collabs = Collaborator::objects()->filter(array(
                    'user_id' => $user->getId(),
                    'thread__ticket__ticket_id' => $ticket->getId(),
                ));

                // check for this user as the owner of the ticket
                if ((count($collabs) == 0) && ($ticket->getOwnerId() != $user->getId())) {
                    // add user becuase it is not already on ticket
                    $ticket->addCollaborator($user, $settings, $errors);
                }

            }
        }
    }


    function closeUserTickets($user_id) {
        $tickets = TicketModel::objects();
        $tickets->filter(Q::any(array(
            'user_id' => $user_id,
        )));

        foreach ($tickets as $ticketModel) {
            $ticket = Ticket::lookup($ticketModel->getId());
            if ($ticket->isOpen()) {
                if (!$ticket->setStatus('closed')) {
                    $ticket->setStatusId(3);
                }
                // remove collaborators so they do not reopen the ticket
                $ticket->getThread()->removeCollaborators();
            }
        }
    }
    
    function createUser($vars) {
        global $ost;
        // create user
        $user = User::fromVars($vars, true, false);
        if (!$user) {
            // failed to create the user
            $ost->logError("user creation", "user creation failed for " . $this->name, false);
            return false;
        }
        if ($this->config->get('add-collabs')) {
            $this->addUserAsCollaborator($user, $user->getOrganization());
        }

        // register the user
        $acct = $user->getAccount();
        if (!$acct) {
            // we need to register
            return $this->registerUser($user, $vars);
        }

        // account already existed?
        return false;
    }

    function registerUser($user, $vars) {
        global $ost;

        $user->register($vars, $errors);
        if ($errors) {
            // failed to register the user
            $ost->logError("user registration", print_r($errors, true), false);
            return false;
        }
        return true;
    }

}


