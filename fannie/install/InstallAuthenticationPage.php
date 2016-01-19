<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

//ini_set('display_errors','1');
include(dirname(__FILE__) . '/../config.php'); 
if (!class_exists('FannieAPI')) {
    include_once(dirname(__FILE__) . '/../classlib2.0/FannieAPI.php');
}
if (!function_exists('confset')) {
    include(dirname(__FILE__) . '/util.php');
}
if (!function_exists('dropDeprecatedStructure')) {
    include(dirname(__FILE__) . '/db.php');
}

/**
    @class InstallAuthenticationPage
    Class for the Authentication install and config options
*/
class InstallAuthenticationPage extends \COREPOS\Fannie\API\InstallPage {

    protected $title = 'Fannie: Authentication Settings';
    protected $header = 'Fannie: Authentication Settings';

    public $description = "
    Class for the Authentication install and config options page.
    ";

    function body_content()
    {
        global $FANNIE_AUTH_ENABLED;
        include(dirname(__FILE__) . '/../config.php'); 

        ob_start();
        echo showInstallTabs('Authentication');
?>

<form action=InstallAuthenticationPage.php method=post>
<?php
echo $this->writeCheck(dirname(__FILE__) . '/../config.php');
?>
<hr />
<p class="ichunk" style="margin-top: 1.0em;">
<b>Authentication enabled</b>
<?php echo installSelectField('FANNIE_AUTH_ENABLED', $FANNIE_AUTH_ENABLED,
                       array(1 => 'Yes', 0 => 'No'), false, false); ?>
</p><!-- /.ichunk -->
<?php
// Default to Authenticate ("Authenticate Everything") or not.
if ($FANNIE_AUTH_ENABLED){
    echo "<p class='ichunk'>";
    echo "<b>Authenticate by default </b>";
    echo installSelectField('FANNIE_AUTH_DEFAULT', $FANNIE_AUTH_DEFAULT,
                           array(1 => 'Yes', 0 => 'No'), false, false);
    echo "If 'Yes' all Admin utilities will require Login<br />";
    echo "If 'No' only those utilities coded for it will require Login";
    echo "</p><!-- /.ichunk -->";
}

if ($FANNIE_AUTH_ENABLED){
    if (!function_exists("login"))
        include($FANNIE_ROOT.'auth/login.php');

    // if no users exist, offer to create one
    if (getNumUsers() == 0){
        $success = False;
        if (isset($_REQUEST['newuser']) && isset($_REQUEST['newpass'])){
            $FANNIE_AUTH_ENABLED = False; // toggle to bypass user checking
            $newUser=$_REQUEST['newuser'];
            $success = createLogin($_REQUEST['newuser'],$_REQUEST['newpass']);
            if ($success){
                echo "<i>User ".$_REQUEST['newuser']." created</i><br />";
                $FANNIE_AUTH_ENABLED = True; // toggle enforce error checking
                $success = addAuth($_REQUEST['newuser'],'admin');
                if ($success) {
                    echo "<i>User ".$_REQUEST['newuser']." is an admin</i><br />";
                    echo "You can use these credentials at the <a href='../auth/ui/' target='_aui'>Authentication Interface</a></br />";
                    echo " Other protected pages may require different credentials.<br />";
                    $success = addAuth($_REQUEST['newuser'],'sysadmin');
                    if ($success) {
                        echo "<i>User ".$_REQUEST['newuser']." is a sysadmin</i><br />";
                        echo "You can use these credentials at the Installation and Configuration Interface (these pages)</br />";

                        // populate known privileges table automatically
                        $db = FannieDB::get($FANNIE_OP_DB);
                        ob_start(); // don't care about primary key errors
                        \COREPOS\Fannie\API\data\DataLoad::loadSampleData($db, 'userKnownPrivs');
                        ob_end_clean();
                        // loaddata() has no return value; success assumed.
                        echo "Table {$FANNIE_OP_DB}.userKnownPrivs has been populated with the standard privilege set.<br />";

                    } else {
                        echo "<b>Error making user $newUser a sysadmin</b><br />";
                    }

                } else {
                    echo "<b>Error making user $newUser an admin</b><br />";
                }
            }
            else 
                echo "<b>Error creating initial user</b><br />";
            $FANNIE_AUTH_ENABLED = True; // toggle enforce error checking
        }
        if (!$success){
            echo "<br /><i>No users defined. To create an initial admin user,
                enter a username and password below</i><br />";
            echo 'Username: <input type="text" name="newuser" /><br />';
            echo 'Password: <input type="password" name="newpass" /><br />';
        }
    }
    else {
        echo "<p class='ichunk'>You can manage Login users and groups via the <a href='../auth/ui/' target='_aui'>Authentication Interface</a>";
        echo "</p><!-- /.ichunk -->";
    }
    echo "<p class='ichunk'><a href='../../documentation/Fannie/developer/auth.html' target='_audoc'>How Authentication Works</a>";
    echo "</p><!-- /.ichunk -->";
}
?>
<hr />
<b>Allow shadow logins</b>
<?php 
echo installSelectField('FANNIE_AUTH_SHADOW', $FANNIE_AUTH_SHADOW,
                   array(1 => 'Yes', 0 => 'No'), false, false);
if (!file_exists("../auth/shadowread/shadowread")){
    echo "<div class=\"alert alert-danger\"><b>Error</b>: shadowread utility does not exist</div>";
    echo "<div class=\"well\">";
    echo "shadowread lets Fannie authenticate users agaist /etc/shadow. To create it:";
    echo "<pre>
cd ".realpath('../auth/shadowread')."
make
    </pre>";
    echo "</div>";
} else {
    $perms = fileperms("../auth/shadowread/shadowread");
    if ($perms == 0104755)
        echo "<div class=\"alert alert-success\">shadowread utility has proper permissions</div>";
    else{
        echo "<div class=\"alert alert-danger\"><b>Warning</b>: shadowread utility has incorrect permissions</div>";
        echo "<div class=\"well\">";
        echo "shadowread needs setuid permission. To fix it: ";
        echo "<pre>
cd ".realpath('../auth/shadowread')."
sudo make install
        </pre>";
        echo "</div>";
    }
}
?>
<hr />
<b>Allow LDAP logins</b>
<?php 
echo installSelectField('FANNIE_AUTH_LDAP', $FANNIE_AUTH_LDAP,
               array(1 => 'Yes', 0 => 'No'), false, false);
if (!function_exists("ldap_connect"))
    echo "<div class=\"alert alert-danger\"><b>Warning</b>: PHP install does not have LDAP support enabled</div>";
else
    echo "<div class=\"alert alert-success\">PHP has LDAP support enabled</div>";
?>
<br />
<label>LDAP Server Host</label>
<?php echo installTextField('FANNIE_LDAP_SERVER', $FANNIE_LDAP_SERVER, '127.0.0.1'); ?>
<label>LDAP Port</label>
<?php echo installTextField('FANNIE_LDAP_PORT', $FANNIE_LDAP_PORT, '389'); ?>
<label>LDAP Domain (DN)</label>
<?php echo installTextField('FANNIE_LDAP_DN', $FANNIE_LDAP_DN, 'ou=People,dc=example,dc=org'); ?>
<label>LDAP Username Field</label>
<?php echo installTextField('FANNIE_LDAP_SEARCH_FIELD', $FANNIE_LDAP_SEARCH_FIELD, 'uid'); ?>
<label>LDAP User ID# Field</label>
<?php echo installTextField('FANNIE_LDAP_UID_FIELD', $FANNIE_LDAP_UID_FIELD, 'uidnumber'); ?>
<label>LDAP Real Name Field</label>
<?php echo installTextField('FANNIE_LDAP_RN_FIELD', $FANNIE_LDAP_RN_FIELD, 'cn'); ?>
<hr />
<p>
    <button type=submit class="btn btn-default">Save Configuration</button>
</p>
</form>

<?php

        return ob_get_clean();

    // body_content
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->body_content()));
    }

// InstallAuthenticationPage
}

FannieDispatch::conditionalExec();

