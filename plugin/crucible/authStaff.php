<?php
/*
Crucible Plugin for osTicket
Copyright 2020 Carnegie Mellon University.
NO WARRANTY. THIS CARNEGIE MELLON UNIVERSITY AND SOFTWARE ENGINEERING INSTITUTE MATERIAL IS FURNISHED ON AN "AS-IS" BASIS. CARNEGIE MELLON UNIVERSITY MAKES NO WARRANTIES OF ANY KIND, EITHER EXPRESSED OR IMPLIED, AS TO ANY MATTER INCLUDING, BUT NOT LIMITED TO, WARRANTY OF FITNESS FOR PURPOSE OR MERCHANTABILITY, EXCLUSIVITY, OR RESULTS OBTAINED FROM USE OF THE MATERIAL. CARNEGIE MELLON UNIVERSITY DOES NOT MAKE ANY WARRANTY OF ANY KIND WITH RESPECT TO FREEDOM FROM PATENT, TRADEMARK, OR COPYRIGHT INFRINGEMENT.
Released under a GNU GPL 2.0-style license, please see license.txt or contact permission@sei.cmu.edu for full terms.
[DISTRIBUTION STATEMENT A] This material has been approved for public release and unlimited distribution.  Please see Copyright notice for non-US Government use and distribution.
Carnegie Mellon(R) and CERT(R) are registered in the U.S. Patent and Trademark Office by Carnegie Mellon University.
This Software includes and/or makes use of the following Third-Party Software subject to its own license:
1. osTicket Plugins (https://github.com/osTicket/osTicket-plugins/blob/develop/LICENSE) Copyright 2013 Free Software Foundation, Inc..
2. osticket-rocketchat (https://github.com/tuudik/osticket-rocketchat/blob/master/LICENSE) Copyright 2016 Tuudik, laufhannes, thammanna.
DM20-0195
*/

use ohmy\Auth2;

class StaffAuthBackend extends ExternalStaffAuthenticationBackend {
    static $id = "identity.staff";
    static $name = "Identity";

    static $sign_in_image_url = ROOT_PATH . "assets/oauth/images/sketch.gif";
    static $service_name = "Identity";

    var $config;

    function __construct($config) {
        $this->config = $config;
        $this->identity= new Auth($config);
    }

    function signOn() {
        // TODO: Check session for auth token
        if (isset($_SESSION[':oauth']['username'])) {
            if (($staff = StaffSession::lookup(array('username' => $_SESSION[':oauth']['username'])))
                && $staff->getId()) {
                if (!$staff instanceof StaffSession) {
                    // osTicket <= v1.9.7 or so
                    $staff = new StaffSession($user->getId());
                }
                return $staff;
            } else {
                $_SESSION['_staff']['auth']['msg'] = 'Have your administrator create a local account';
            }
        }
    }

    static function signOut($user) {
        parent::signOut($user);
        unset($_SESSION[':oauth']);
    }

    function triggerAuth() {
        global $ost;

        parent::triggerAuth();
        $identity = $this->identity->triggerAuth();

        try {
            // if neither agent nor admin is set, this is a regular user
            if ((!$this->identity->is_agent) && (!$this->identity->is_admin)) {
                if (!$_SESSION['_staff']['auth']['msg']) {
                    $_SESSION['_staff']['auth']['msg'] = 'must be agent or admin';
                }
            } else if ($this->checkUser()) {
                $_SESSION[':oauth']['username'] = $this->identity->guid;
            }
        }
        catch (Exception $e) {
            $ost->logError("triggerAuth - Staff", $e->getMessage(), false);
            throw $e;
        }

        Http::redirect(ROOT_PATH . 'scp/index.php');
    }

    function checkUser() {
        global $ost;

        // for agents we need first and last name
        // try to use name field for the first name, because that is what gets displayed
        if ($this->identity->name) {
            $firstname = $this->identity->name;
        } else {
            // if there is no name, use the email field
            $parts = preg_split("/[@]/", $this->identity->email);
            if (count($parts) > 0) {
                $firstname = $parts[0];
            } else {
                // if no email field, use the id guid
                $firstname = $this->identity->guid;
            }
        }
        // last name is a space, because it is required, but doesn't get displayed
        $lastname = ' ';
        
        $vars = array(
            'email' => $this->identity->email,
            'backend' => "identity.staff",
            'isadmin' => $this->identity->is_admin,
            'auto_refresh_rate' => "1", // 1 minute
            'firstname' => $firstname,
            'lastname' => $lastname,
            'username' => $this->identity->guid,
            'dept_id' => $ost->getConfig()->getDefaultDeptId(),
            'role_id' => "1" // agent, not admin
        );

        // // lookup client user account
        // $account = UserAccount::lookupByUsername($this->identity->guid);
        // if ($account) {
        //     // we should not allow login
        //     $_SESSION['_staff']['auth']['msg'] = 'user cannot login as staff';
        //     return false;
        // }

        // lookup staff user account
        $staff_id = Staff::getIdByUsername($this->identity->guid);
        if (!$staff_id) {
            // we need to create the user
            if (!$this->createUser($vars)) {
                return false;
            }
        }

        // lookup staff user account
        $objects = Staff::objects()->filter(array('username' => $this->identity->guid));
        if (count($objects) == 1) {
            $staff = $objects[0];
            // set access
            if ($this->config->get('add-extended-access')) {
                $this->setAccess($staff);
            }
            // keep firstname and lastname updated
            if ($staff->firstname != $firstname || $staff->lastname != $lastname) {
                $staff->firstname = $firstname;
                $staff->lastname = $lastname;
                $staff->save();
            }
            if ($this->config->get('identity-email') && (!isset($staff->email) || $this->identity->email != $staff->email)) {
                $staff->email = $this->identity->email;
                $staff->save();
            }
        } else {
            $_SESSION['_staff']['auth']['msg'] = 'could not locate account';
            return false;
        }

        return true;
    }

    function setAccess($staff) {

        // check all departments
        $depts = Dept::getDepartments();
        foreach ($depts as $dept) {
            $match = false;
            $dept_id = Dept::getIdByName($dept);

            // check if user is in this dept as primary
            if ($staff->dept_id == $dept_id) {
                continue;
            }
        
            foreach ($staff->dept_access as $access) {
                if ($dept_id == $access->dept_id) {
                    $match = true;
                    break;
                }
            }
            if ($match) {
                // user is in this dept with extended access
                continue;
            }
        
            // add the user to the department with extended access
            $da = new StaffDeptAccess(array(
                'dept_id' => $dept_id,
                'role_id' => 4, // view only
            ));
            $staff->dept_access->add($da);
            $da->save();
        }
        
    }

    function createUser($vars) {
        global $ost;

        // check column size
        $this->identity->checkColumn();

        // create user
        $staff = Staff::create();
        if (!$staff) {
            // failed to create the user
            $_SESSION['_staff']['auth']['msg'] = 'could not create staff account';
            $ost->logError("staff create", "could not create staff account for " . $this->name, false);
            return false;
        }

        // we might want to log these errors is we cannot set these values
        if (!$staff->update($vars, $errors)) {
            // could not update the account settings
            $_SESSION['_staff']['auth']['msg'] = 'could not update staff account';
            $ost->logError("staff update", print_r($errors, true), false);
            $ost->logError("staff update - identity", print_r($this->identity, true), false);
            $ost->logError("staff update - staff", print_r($staff, true), false);
            return false;
        }

        $staff->updatePerms(array(
            User::PERM_CREATE,
            User::PERM_EDIT,
            User::PERM_DELETE,
            User::PERM_MANAGE,
            User::PERM_DIRECTORY,
            Organization::PERM_CREATE,
            Organization::PERM_EDIT,
            Organization::PERM_DELETE,
            FAQ::PERM_MANAGE,
        ));

        $staff->updateProfile($vars, $errors);
        if (!$staff->save()) {
            $_SESSION['_staff']['auth']['msg'] = 'could not update staff profile';
            $ost->logError("staff profile", print_r($errors, true), false);
            return false;
        }

        return true;
    }
}

