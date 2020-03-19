/*
Crucible
Copyright 2020 Carnegie Mellon University.
NO WARRANTY. THIS CARNEGIE MELLON UNIVERSITY AND SOFTWARE ENGINEERING INSTITUTE MATERIAL IS FURNISHED ON AN "AS-IS" BASIS. CARNEGIE MELLON UNIVERSITY MAKES NO WARRANTIES OF ANY KIND, EITHER EXPRESSED OR IMPLIED, AS TO ANY MATTER INCLUDING, BUT NOT LIMITED TO, WARRANTY OF FITNESS FOR PURPOSE OR MERCHANTABILITY, EXCLUSIVITY, OR RESULTS OBTAINED FROM USE OF THE MATERIAL. CARNEGIE MELLON UNIVERSITY DOES NOT MAKE ANY WARRANTY OF ANY KIND WITH RESPECT TO FREEDOM FROM PATENT, TRADEMARK, OR COPYRIGHT INFRINGEMENT.
Released under a MIT (SEI)-style license, please see license.txt or contact permission@sei.cmu.edu for full terms.
[DISTRIBUTION STATEMENT A] This material has been approved for public release and unlimited distribution.  Please see Copyright notice for non-US Government use and distribution.
Carnegie Mellon® and CERT® are registered in the U.S. Patent and Trademark Office by Carnegie Mellon University.
DM20-0181
*/

<?php

require_once('lib/jumbojett/openid-connect-php/OpenIDConnectClient.php');

use Jumbojett\OpenIDConnectClient;

class Auth {
    var $config;
    var $access_token;
    var $is_agent;
    var $is_admin;
    var $teams;
    var $org_id;
    var $guid;
    var $email;
    var $name;

    function __construct($config) {
        $this->config = $config;
    }

    function triggerAuth() {
        global $ost;

        $self = $this;

        $oidc = new OpenIDConnectClient($this->config->get('identity-url'),
            $this->config->get('client-id'),
            $this->config->get('client-secret'));

        if ($this->config->get('ignore-ssl')) {
            $oidc->setVerifyHost(false);
            $oidc->setVerifyPeer(false);
        }

        $oidc->setRedirectURL($this->config->get('redirect-uri'));
        $oidc->addScope('email openid profile player');
        try {
            $oidc->authenticate();
        } catch (Exception $e) {
            $ost->logError("oidc error", $e->getMessage(), false);
            $ost->logError("login error", "Error communicating with identity server", false);
            $this->sendErrorPage();
        }

        $self->access_token = $oidc->getAccessToken();

        $this->getUserInfo($oidc->getVerifiedClaims());

        if (!$this->getTeams()) {
            $ost->logError("login error", "error communicating with player api", false);
            $this->sendErrorPage();
        }
    }

    function sendErrorPage() {
        echo '
        <!DOCTYPE html>
        <html lang="en_US">
        <head>
        <title>Error</title>
        <link rel="stylesheet" href="' . ROOT_PATH . 'assets/default/css/bootstrap.min.css" media="screen">
        <link rel="stylesheet" href="' . ROOT_PATH . 'assets/default/css/bootstrap-theme.css" media="screen">
        <link type="text/css" rel="stylesheet" href="' . ROOT_PATH . 'css/font-awesome.min.css">
        </head>

        <body>
        <div class="wrapper">
            <nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
                <div class="container-fluid">
                    <div class="navbar-header">
                        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#myNavbar">
                            <span class="icon-bar white"></span>
                            <span class="icon-bar white"></span>
                            <span class="icon-bar white"></span> 
                    </button>
                        <a class="navbar-left" href="' . ROOT_PATH . 'index.php" title="Support Center">
                            <img class="img-responsive" src="' . ROOT_PATH . 'logo.php" border=0 alt="Help Desk">
                        </a>
                    </div>
                    <div class="collapse navbar-collapse" id="myNavbar">
                        <ul class="nav navbar-nav navbar-right">
                            <li role="presentation" class="active"><a class="home" href' . ROOT_PATH . 'index.php">Support Center Home</a></li>
                            <li role="presentation" class=""><a class="new" href="' . ROOT_PATH . 'open.php">Open a New Ticket</a></li>
                            <li role="presentation" class=""><a class="status" href="' . ROOT_PATH . 'view.php">Check Ticket Status</a></li>
                            <li><a href="' . ROOT_PATH . 'login.php"><span class="glyphicon glyphicon-log-in white"></span> Sign In</a></li>
                        </ul>
                    </div>
                </div>
            </nav>

            <div class="clearfix"></div>
            <div class="container">
                <div class="row"> 
                    <div class="col-md-12">
                            <!--End of header-->
                        <div class="row">
                            <div class="page-title">';

        if ($this->name) {
            echo '<h1>Your team could not be determined, ' . $this->name . ', please seek assistance</h1>';
        } else {
            echo '<h1>Your identity could not be determined, please seek assistance</h1>';
        }
        echo '
                                <p><a href="' . ROOT_PATH . 'login.php" >Return to login page</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer"> 
            <div class="company">
                Copyright &copy; 2018 Carnegie Mellon University - All rights reserved.
            </div>
            <div class="poweredBy col-md-offset-10 col-xs-offset-6"">
                Powered by					<a href="http://www.osticket.com" target="_blank"> <img alt="osTicket" src="' . ROOT_PATH . 'scp/images/osticket-grey.png" class="osticket-logo"> </a>
            </div>
        </div>
        </body>
        </html>
        ';
        exit;
    }

    function getUserInfo($payload) {

        $this->guid = $payload->sub;
        $this->name = $payload->name;

        if ($payload->email) {
            $this->email = $payload->email;
        }

        if (!$this->email) {
            $this->email = "$guid@localhost";
        }

        return true;
    }

    function getTeams() {
        global $ost;

        if (!$this->access_token) {
            $_SESSION['_staff']['auth']['msg'] = 'cannot get access token';
            $ost->logError("login error", "no access token set", false);
            return false;
        }
        $authorization = "Authorization: Bearer " . $this->access_token;

        // get users teams in the configured exercise
        $url = $this->config->get('player-api-url') . '/me/exercises/' .
                $this->config->get('exercise-guid') . '/teams';
        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Accept: application/json',
                $authorization
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

        if ($this->config->get('ignore-ssl') == true) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        }

        $response = curl_exec($curl);
        curl_close($curl);

        if (null === ($teams = json_decode($response))) {
            $ost->logError("login error", "null json response", false);
            return false;
        }
        if (count($teams) == 0) {
            // if no team, user probably shouldnt be able to login at all
            $ost->logError("login error", "no teams for " . $this->name, false);
            return false;
        }

        $this->teams = $teams;
        $this->is_agent = $this->isAgent();
        $this->is_admin = $this->isAdmin();

        // check for staff status
        if (($this->is_admin) || ($this->is_agent)) {
            return true;
        } else {
            // check other teams if not agent or admin
            foreach ($this->teams as $team) {
                if ($team->isPrimary == true) {
                    $this->getTeamAsOrg($team->name);
                    return true;
                }
            }
        }
        $ost->logError("login error", "no primary team found for " . $this->name, false);

        return false;
    }

    function getTeamAsOrg($name) {

        $orgs = Organization::objects()->filter(array(
            'name'=>$name,
        ));
        $count = count($orgs);

        if ($count == 0) {
            $this->createOrgFromTeam($name);
        } else if ($count == 1) {
            $this->org_id = $orgs[0]->getId();
        }
    }

    function createOrgFromTeam($name) {
        global $ost;

        // create org
        $vars = array(
            'name' => $name
        );

        $org = Organization::fromVars($vars);

        if ($org) {
            $this->org_id = $org->getId();

            // set sharing-all
            $this->setSharing($name);
        } else {
            $ost->logError("login error", "could not create new organziation  " . $name, false);
        }
    }

    function setSharing($name) {

        $orgs = Organization::objects()->filter(array(
            'name'=>$name,
        ));

        $orgs->update(array('status'=> Organization::SHARE_EVERYBODY | Organization::COLLAB_ALL_MEMBERS));
    }

    function isAgent() {

        foreach ($this->teams as $team) {
            $agentTeamGuidsString = $this->config->get('agent-guid-list');
            $agentTeamGuids = explode(",", $agentTeamGuidsString);
            foreach ($agentTeamGuids as $agentTeamGuid) {
                if (($team->id == $agentTeamGuid) && ($team->isPrimary == true)) {
                    return true;
                }
            }
        }
        return false;
    }

    function isAdmin() {

        foreach ($this->teams as $team) {
            $adminTeamGuidsString = $this->config->get('admin-guid-list');
            $adminTeamGuids = explode(",", $adminTeamGuidsString);
            foreach ($adminTeamGuids as $adminTeamGuid) {
                if (($team->id == $adminTeamGuid) && ($team->isPrimary == true)) {
                    return true;
                }
            }
        }
        return false;
    }

    function checkColumn() {
        global $ost;

        $sql = "SELECT character_maximum_length FROM information_schema.columns WHERE table_schema = Database() AND table_name = '" . TABLE_PREFIX . "staff' AND column_name = 'username'";
        $results = db_query($sql);
        if (!$results) {
                return false;
        }
        if ($results->num_rows == 1) {
                $result = $results->fetch_row();
        } else {
                return false;
        }

        if ($result[0] < 64) {
            // maybe this could be a different check
            // echo "updating $result[0] to VARCHAR(64)<br>";
            $sql = "ALTER TABLE " . TABLE_PREFIX . "staff MODIFY username VARCHAR(64)";
            $results = db_query($sql);
            if (!$results) {
                return false;
            }
            $ost->logDebug("db update", "updated staff table username size", false);
        }
    }
}

