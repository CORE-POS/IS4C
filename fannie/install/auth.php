<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/


/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

	* 10Nov2012 Eric Lee Add include ../auth/login.php
	*                    Fix closing of Authentication Enabled select.

*/

ini_set('display_errors','1');
?>
<?php 
include('../config.php'); 
include('util.php');
include('db.php');
?>
<a href="index.php">Necessities</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
Authentication
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="mem.php">Members</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="stores.php">Stores</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="update.php">Updates</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="plugins.php">Plugins</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="sample_data/extra_data.php">Sample Data</a>
<form action=auth.php method=post>
<h1>Fannie install checks</h1>
<?php
// path detection
$FILEPATH = rtrim($_SERVER['SCRIPT_FILENAME'],'auth.php');
$FILEPATH = rtrim($FILEPATH,'/');
$FILEPATH = rtrim($FILEPATH,'install');
$FANNIE_ROOT = $FILEPATH;

if (is_writable($FILEPATH.'config.php')){
	echo "<span style=\"color:green;\"><i>config.php</i> is writeable</span>";
}
else {
	echo "<span style=\"color:red;\"><b>Error</b>: config.php is not writeable</span>";
}
?>
<br /><br />
<b>Authentication enabled</b>
<select name=FANNIE_AUTH_ENABLED>
<?php
if (!isset($FANNIE_AUTH_ENABLED)) $FANNIE_AUTH_ENABLED = False;
if (isset($_REQUEST['FANNIE_AUTH_ENABLED'])) $FANNIE_AUTH_ENABLED = $_REQUEST['FANNIE_AUTH_ENABLED'];
if ($FANNIE_AUTH_ENABLED === True || $FANNIE_AUTH_ENABLED == 'Yes'){
	confset('FANNIE_AUTH_ENABLED','True');
	echo "<option selected>Yes</option><option>No</option>";
}
else{
	confset('FANNIE_AUTH_ENABLED','False');
	echo "<option>Yes</option><option selected>No</option>";
}
//10Nov12 EL New location for </select>
echo "</select><br />";
if ($FANNIE_AUTH_ENABLED){
	include("../auth/utilities.php");
	//10Nov12 EL Added include login.php
	include("../auth/login.php");
	table_check(); // create user tables

	// if no users exist, offer to create one
	if (getNumUsers() == 0){
		$success = False;
		if (isset($_REQUEST['newuser']) && isset($_REQUEST['newpass'])){
			$FANNIE_AUTH_ENABLED = False; // toggle to bypass user checking
			$success = createLogin($_REQUEST['newuser'],$_REQUEST['newpass']);
			if ($success){
				echo "<i>User ".$_REQUEST['newuser']." created</i><br />";
				$FANNIE_AUTH_ENABLED = True; // toggle enforce error checking
				$success = addAuth($_REQUEST['newuser'],'admin');
				if ($success) {
					echo "<i>User ".$_REQUEST['newuser']." is an admin</i><br />";
					// 10Nov12 EL Added these notes to the person installing.
					echo "You can use these credentials at the <a href='../auth/ui/' target='_aui'>Authentication Interface</a></br />";
					echo " Other protected pages may require different credentials.<br />";

					// populate known privileges table automatically
					if (!class_exists('FannieDB'))
						include($FANNIE_ROOT.'classlib2.0/data/FannieDB.php');
					$db = FannieDB::get($FANNIE_OP_DB);
					loaddata($db, 'userKnownPrivs');


				} else {
					echo "<b>Error making user an admin</b><br />";
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
		echo "You can manage users and groups via the <a href='../auth/ui/' target='_aui'>Authentication Interface</a></br />";
	}
}
?>
<!-- 10Nov12 EL Formerly.
</select>
-->
<hr />
<b>Allow shadow logins</b>
<select name=FANNIE_AUTH_SHADOW>
<?php
if (!isset($FANNIE_AUTH_SHADOW)) $FANNIE_AUTH_SHADOW = False;
if (isset($_REQUEST['FANNIE_AUTH_SHADOW'])) $FANNIE_AUTH_SHADOW = $_REQUEST['FANNIE_AUTH_SHADOW'];
if ($FANNIE_AUTH_SHADOW === True || $FANNIE_AUTH_SHADOW == 'Yes'){
	confset('FANNIE_AUTH_SHADOW','True');
	echo "<option selected>Yes</option><option>No</option>";
}
else{
	confset('FANNIE_AUTH_SHADOW','False');
	echo "<option>Yes</option><option selected>No</option>";
}
echo "</select><br />";
if (!file_exists("../auth/shadowread/shadowread")){
	echo "<span style=\"color:red;\"><b>Error</b>: shadowread utility does not exist</span>";
	echo "<blockquote>";
	echo "shadowread lets Fannie authenticate users agaist /etc/shadow. To create it:";
	echo "<pre style=\"font:fixed;background:#ccc;\">
cd ".realpath('../auth/shadowread')."
make
	</pre>";
	echo "</blockquote>";
}
else {
	$perms = fileperms("../auth/shadowread/shadowread");
	if ($perms == 0104755)
		echo "<span style=\"color:green;\">shadowread utility has proper permissions</span>";
	else{
		echo "<span style=\"color:red;\"><b>Warning</b>: shadowread utility has incorrect permissions</span>";
		echo "<blockquote>";
		echo "shadowread needs setuid permission. To fix it: ";
		echo "<pre style=\"font:fixed;background:#ccc;\">
cd ".realpath('../auth/shadowread')."
sudo make install
		</pre>";
		echo "</blockquote>";
	}
}
?>
<hr />
<b>Allow LDAP logins</b>
<select name=FANNIE_AUTH_LDAP>
<?php
if (!isset($FANNIE_AUTH_LDAP)) $FANNIE_AUTH_LDAP = False;
if (isset($_REQUEST['FANNIE_AUTH_LDAP'])) $FANNIE_AUTH_LDAP = $_REQUEST['FANNIE_AUTH_LDAP'];
if ($FANNIE_AUTH_LDAP === True || $FANNIE_AUTH_LDAP == 'Yes'){
	confset('FANNIE_AUTH_LDAP','True');
	echo "<option selected>Yes</option><option>No</option>";
}
else{
	confset('FANNIE_AUTH_LDAP','False');
	echo "<option>Yes</option><option selected>No</option>";
}
?>
</select><br />
<?php
if (!function_exists("ldap_connect"))
	echo "<span style=\"color:red;\"><b>Warning</b>: PHP install does not have LDAP support enabled</span>";
else
	echo "<span style=\"color:green;\">PHP has LDAP support enabled</span>";
?>
<br />
LDAP Server Host
<?php
if(!isset($FANNIE_LDAP_SERVER)) $FANNIE_LDAP_SERVER = '127.0.0.1';
if (isset($_REQUEST['FANNIE_LDAP_SERVER'])){
	$FANNIE_LDAP_SERVER = $_REQUEST['FANNIE_LDAP_SERVER'];
}
confset('FANNIE_LDAP_SERVER',"'$FANNIE_LDAP_SERVER'");
echo "<input type=text name=FANNIE_LDAP_SERVER value=\"$FANNIE_LDAP_SERVER\" />";
?>
<br />
LDAP Port
<?php
if(!isset($FANNIE_LDAP_PORT)) $FANNIE_LDAP_PORT = 389;
if (isset($_REQUEST['FANNIE_LDAP_PORT'])){
	$FANNIE_LDAP_PORT = $_REQUEST['FANNIE_LDAP_PORT'];
}
confset('FANNIE_LDAP_PORT',"'$FANNIE_LDAP_PORT'");
echo "<input type=text name=FANNIE_LDAP_PORT value=\"$FANNIE_LDAP_PORT\" />";
?>
<br />
LDAP Domain (DN)
<?php
if(!isset($FANNIE_LDAP_DN)) $FANNIE_LDAP_DN = 'ou=People,dc=example,dc=org';
if (isset($_REQUEST['FANNIE_LDAP_DN'])){
	$FANNIE_LDAP_DN = $_REQUEST['FANNIE_LDAP_DN'];
}
confset('FANNIE_LDAP_DN',"'$FANNIE_LDAP_DN'");
echo "<input type=text name=FANNIE_LDAP_DN value=\"$FANNIE_LDAP_DN\" />";
?>
<br />
LDAP Username Field 
<?php
if(!isset($FANNIE_LDAP_SEARCH_FIELD)) $FANNIE_LDAP_SEARCH_FIELD = 'uid';
if (isset($_REQUEST['FANNIE_LDAP_SEARCH_FIELD'])){
	$FANNIE_LDAP_SEARCH_FIELD = $_REQUEST['FANNIE_LDAP_SEARCH_FIELD'];
}
confset('FANNIE_LDAP_SEARCH_FIELD',"'$FANNIE_LDAP_SEARCH_FIELD'");
echo "<input type=text name=FANNIE_LDAP_SEARCH_FIELD value=\"$FANNIE_LDAP_SEARCH_FIELD\" />";
?>
<br />
LDAP User ID# Field 
<?php
if(!isset($FANNIE_LDAP_UID_FIELD)) $FANNIE_LDAP_UID_FIELD = 'uidnumber';
if (isset($_REQUEST['FANNIE_LDAP_UID_FIELD'])){
	$FANNIE_LDAP_UID_FIELD = $_REQUEST['FANNIE_LDAP_UID_FIELD'];
}
confset('FANNIE_LDAP_UID_FIELD',"'$FANNIE_LDAP_UID_FIELD'");
echo "<input type=text name=FANNIE_LDAP_UID_FIELD value=\"$FANNIE_LDAP_UID_FIELD\" />";
?>
<br />
LDAP Real Name Field 
<?php
if(!isset($FANNIE_LDAP_RN_FIELD)) $FANNIE_LDAP_RN_FIELD = 'cn';
if (isset($_REQUEST['FANNIE_LDAP_RN_FIELD'])){
	$FANNIE_LDAP_RN_FIELD = $_REQUEST['FANNIE_LDAP_RN_FIELD'];
}
confset('FANNIE_LDAP_RN_FIELD',"'$FANNIE_LDAP_RN_FIELD'");
echo "<input type=text name=FANNIE_LDAP_RN_FIELD value=\"$FANNIE_LDAP_RN_FIELD\" />";
?>
<hr />
<input type=submit value="Re-run" />
</form>
