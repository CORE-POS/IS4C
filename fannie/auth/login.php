<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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

/*
this file contains user authentication-related functions
all functions return true on success, false on failure
unless otherwise noted
*/


/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    * 10Nov12 Eric Lee Add FANNIE_AUTH_ENABLED test in createLogin per intent(?)
    *                   in first-user call from install/auth.php.

*/

require_once('groups.php');

/*
a user is logged in using cookies
when a user logs in, a cookie name 'session_data' is created containing
two pieces of data: the user's name and a session_id which is
a 50 character random string of digits and capital letters
to access this data, unserialize the cookie's value and use
keys 'name' and 'session_id' to access the array
because this function sets a cookie, nothing before this function
call can produce output
*/
function login($name,$password){
  if (!isAlphanumeric($name)){
    return false;
  }
  if ($password == "") return false;

  $sql = dbconnect();
  $gatherQ = $sql->prepare("select password,salt from Users where name=?");
  $gatherR = $sql->execute($gatherQ,array($name));
  if ($sql->num_rows($gatherR) == 0){
    return false;
  }
  
  $gatherRow = $sql->fetchRow($gatherR);
  $crypt_pass = $gatherRow[0];
  $salt = $gatherRow[1];
  if (!checkPass($password, $crypt_pass, $salt, $name)) {
  //if (crypt($password,$salt) != $crypt_pass){
    return false;
  }

  doLogin($name);

  return true;
}

function checkPass($password, $crypt_pass, $salt, $name) {
    if (!function_exists('password_hash')) {
        return crypt($password, $salt) == $crypt_pass;
    } elseif (!empty($salt)) {
        if (crypt($password, $salt) == $crypt_pass) {
            migratePass($name, $password);
            return true;
        } else {
            return false;
        }
    } else {
        return password_verify($password, $crypt_pass); 
    }
}

function migratePass($name, $password) {
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    if (password_verify($password, $hashed)) {
        $dbc = dbconnect();
        $table = $dbc->detailedDefinition('Users');
        foreach ($table as $col_name => $info) {
            if ($col_name === 'password' && $info['type'] === 'VARCHAR(255)') {
                $upP = $dbc->prepare('
                    UPDATE Users SET password=?, salt=\'\'
                    WHERE name=?');
                $upR = $dbc->execute($upP, array($hashed, $name));

                return $upR;
            }
        }
    }
    return false;
}

/* 
    Revised login for use with UNIX system
    
    shadowread searches the shadow password file
    and returns the user's password hash
*/

function shadow_login($name,$passwd){
    if (!isAlphanumeric($name))
        return false;
    if ($passwd == "") return false;

    $output = array();
    $return_value = -1;
    exec("../shadowread/shadowread \"$name\"",$output,$return_value);
    if ($return_value != 0)
        return false;

    $pwhash = $output[0];
    if (crypt($passwd,$pwhash) == $pwhash){
        syncUserShadow($name);
        doLogin($name);
        return true;
    }   
    return false;
}

/* login using an ldap server 
 * 
 * Tested against openldap 2.3.27
 */
function ldap_login($name,$passwd)
{
    $config = FannieConfig::factory();
    if (!isAlphanumeric($name))
        return false;
    if ($passwd == "") return false;

    $conn = ldap_connect($config->get('LDAP_SERVER'), $config->get('LDAP_PORT'));
    if (!$conn) return false;

    $user_dn = $config->get('LDAP_SEARCH_FIELD').'='.$name.','.$config->get('LDAP_DN');
    if (!ldap_bind($conn,$user_dn,$passwd)){
        return false;
    }

    $search_result = ldap_search($conn,$config->get('LDAP_DN'),
                     $config->get('LDAP_SEARCH_FIELD')."=".$name);
    if (!$search_result) return false;

    $ldap_info = ldap_get_entries($conn,$search_result);
    if (!$ldap_info) {
        return false;
    } else if ($ldap_info['count'] == 0) {
        return false;
    }

    $uid = $ldap_info[0][$config->get('LDAP_UID_FIELD')][0];
    $fullname = $ldap_info[0][$config->get('LDAP_RN_FIELD')][0];

    syncUserLDAP($name,$uid,$fullname); 
    doLogin($name);
    return true;
}

/*
sets a cookie.  nothing before this function call can have output
*/
function logout(){
    $name = checkLogin();
    if (!$name){
        return true;
    }

    /**
      Remove session data from the database
    */
    if (filter_input(INPUT_COOKIE, 'session_data') !== null) {
        $cookie_data = base64_decode(filter_input(INPUT_COOKIE, 'session_data'));
        $session_data = unserialize($cookie_data);

        $name = $session_data['name'];
        $session_id = $session_data['session_id'];
        $uid = getUID($name);

        $sql = dbconnect();
        $delP = $sql->prepare('DELETE FROM userSessions
                WHERE uid=? AND session_id=?');
        $delR = $sql->execute($delP, array($uid,$session_id));

        $upP = $sql->prepare("UPDATE Users SET session_id='' WHERE name=?");
        $upR = $sql->execute($upP,array($name));
    }

    if (!headers_sent()) {
        setcookie('session_data','',0,'/');
    }
    return true;
}

/*
logins are stored in a table called Users
information in the table includes an alphanumeric
user name, an alphanumeric password (stored in crypted form),
the salt used to crypt the password (time of user creation),
and a unique user-id number between 0001 and 9999
a session id is also stored in this table, but that is created
when the user actually logs in
*/
function createLogin($name,$password){
    // 10Nov12 EL Add FANNIE_AUTH_ENABLED
    global $FANNIE_AUTH_ENABLED;
  if (!isAlphanumeric($name) ){
    //echo 'failed alphanumeric';
    return false;
  }

  if (init_check())

    // 10Nov12 EL Add FANNIE_AUTH_ENABLED test per intent in first-user call from auth.php.
    if ( $FANNIE_AUTH_ENABLED ) {
        if (!validateUser('admin')){
            return false;
        }
  }

  $sql = dbconnect();
  $checkQ = $sql->prepare("select * from Users where name=?");
  $checkR = $sql->execute($checkQ,array($name));
  if ($sql->num_rows($checkR) != 0){
    return false;
  }
  
  $salt = time();
  $crypt_pass = crypt($password,$salt);
  
  // generate unique user-id between 0001 and 9999
  // implicit assumption:  there are less than 10,000
  // Users currently in the database
  $uid = '';
  srand($salt);
  $verifyQ = $sql->prepare("select * from Users where uid=?");
  while ($uid == ''){
    $newid = (rand() % 9998) + 1;
    $newid = str_pad($newid,4,'0',STR_PAD_LEFT);
    $verifyR = $sql->execute($verifyQ,array($newid));
    if ($sql->num_rows($verifyR) == 0){
      $uid = $newid;
    }
  }

  $addQ = $sql->prepare("insert into Users (name,uid,password,salt) values (?,?,?,?)");
  $addR = $sql->execute($addQ,array($name,$uid,$crypt_pass,$salt));

  return true;
}

function deleteLogin($name){
  if (!isAlphanumeric($name)){
    return false;
  }
  
  if (!validateUser('admin')){
    return false;
  }

  $sql=dbconnect();
  $uid = getUID($name);
  $delQ = $sql->prepare("delete from userPrivs where uid=?");
  $delR = $sql->execute($delQ,array($uid));

  $deleteQ = $sql->prepare("delete from Users where name=?");
  $deleteR = $sql->execute($deleteQ,array($name));

  $groupQ = $sql->prepare("DELETE FROM userGroups WHERE username=?");
  $groupR = $sql->execute($groupQ,array($name));

  return true;
}

/* 
this function returns the name of the logged in
user on success, false on failure
*/
function checkLogin(){
  if (!auth_enabled()) return 'null';

  if (init_check())
    return 'init';

  if (filter_input(INPUT_COOKIE, 'session_data') === null) {
    return false;
  }

  $cookie_data = base64_decode(filter_input(INPUT_COOKIE, 'session_data'));
  $session_data = unserialize($cookie_data);

  $name = $session_data['name'];
  $session_id = $session_data['session_id'];

  if (!isAlphanumeric($name) or !isAlphanumeric($session_id)){
    return false;
  }

  /**
    New behavior: use dedicated userSessions table.
    Could enforce expired, optionally
  */
  $sql = dbconnect();
  if (!$sql->isConnected()) {
      return false;
  }
  $checkQ = $sql->prepare("select * from Users AS u LEFT JOIN
            userSessions AS s ON u.uid=s.uid where u.name=? 
            and s.session_id=?");
  $checkR = $sql->execute($checkQ,array($name,$session_id));

  if ($sql->num_rows($checkR) == 0){
    return false;
  }

  return $name;
}

function getUserList()
{
    $sql = dbconnect();
    $ret = array();
    $result = $sql->query('
        SELECT name,
            uid
        FROM Users
        ORDER BY name');
    while ($row = $sql->fetch_row($result)) {
        $ret[$row['uid']] = $row['name'];
    }

    return $ret;
}

/* 
this function uses login to verify the user's presented
name and password (thus creating a new session) rather
than using checkLogin to verify the correct user is
logged in.  This is nonstandard usage.  Normally checkLogin
should be used to determine who (if anyone) is logged in
(this way Users don't have to constantly provide passwords)
However, since the current password is provided, checking
it is slightly more secure than checking a cookie
*/
function changePassword($name,$oldpassword,$newpassword){
  $sql = dbconnect();
  if (!login($name,$oldpassword)){
    return false;
  }

  // the login functions checks its parameters for being
  // alphanumeric, so only the newpassword needs to be checked
  if (!isAlphanumeric($newpassword)){
    return false;
  }

  $salt = time();
  $crypt_pass = crypt($newpassword,$salt);

  $updateQ = $sql->prepare("update Users set password=?,salt=? where name=?");
  $updateR = $sql->execute($updateQ,array($crypt_pass,$salt,$name));
  
  return true;
}

function changeAnyPassword($name,$newpassword){
  $sql = dbconnect();
  if (!validateUser('admin')){
    return false;
  }

  if (!isAlphanumeric($newpassword) || !isAlphaNumeric($name)){
    return false;
  }

  $salt = time();
  $crypt_pass = crypt($newpassword,$salt);

  $updateQ = $sql->prepare("update Users set password=?,salt=? where name=?");
  $updateR = $sql->execute($updateQ,array($crypt_pass,$salt,$name));

  return true;
}

/*
this function is here to reduce user validation checks to
a single function call.  since this task happens ALL the time,
it just makes code cleaner.  It returns the current user on
success just because that information might be useful  
*/
function validateUser($auth,$sub='all'){
     if (!auth_enabled()) return 'null';

     if (init_check())
    return 'init';

     $current_user = checkLogin();
     if (!$current_user){
       echo "You must be logged in to use this function";
       return false;
     }

     $groupPriv = checkGroupAuth($current_user,$auth,$sub);
     if ($groupPriv){
       return $current_user;
     }

     $priv = checkAuth($current_user,$auth,$sub);
     if (!$priv){
       echo "Your account doesn't have permission to use this function";
       return false;
     }
     return $current_user;
}

function validateUserQuiet($auth,$sub='all'){
     if (!auth_enabled()) return 'null';

     if (init_check())
    return 'init';

     $current_user = checkLogin();
     if (!$current_user){
       return false;
     }

     $groupPriv = checkGroupAuth($current_user,$auth,$sub);
     if ($groupPriv){
       return $current_user;
     }

     $priv = checkAuth($current_user,$auth,$sub);
     if (!$priv){
       return false;
     }
     return $current_user;
}

// re-sets expires timer on the cookie if the
// user is currently logged in
// must be called prior to any output
function refreshSession(){
    return true;
    if (filter_input(INPUT_COOKIE, 'session_data') === null)
        return false;
    setcookie('session_data',filter_input(INPUT_COOKIE, 'session_data'),0,'/');
    return true;
}

function pose($username){
    if (filter_input(INPUT_COOKIE, 'session_data') === null)
        return false;
    if (!isAlphanumeric($username))
        return false;

    doLogin($username);

    return true;
}

